/* global adminToolkitS3, ajaxurl */
(function ($) {
    'use strict';

    var PART_SIZE   = 5 * 1024 * 1024; // 5 MB
    var MAX_RETRIES = 3;

    // ── UI helpers ──────────────────────────────────────────────────────────

    function initProgressArea() {
        if ($('#atk-upload-progress').length === 0) {
            $('<div id="atk-upload-progress" style="margin-top:16px;"></div>').insertAfter('#files');
        }
    }

    function upsertProgressBar(filename) {
        var $area = $('#atk-upload-progress');
        var id    = 'atk-bar-' + btoa(unescape(encodeURIComponent(filename))).replace(/[^a-zA-Z0-9]/g, '');

        if ($('#' + id).length === 0) {
            $area.append(
                '<div id="' + id + '" style="margin-bottom:10px;">' +
                  '<strong>' + $('<span>').text(filename).html() + '</strong>' +
                  '<div style="background:#ddd;border-radius:3px;height:18px;margin:4px 0;overflow:hidden;">' +
                    '<div class="atk-fill" style="background:#0073aa;height:100%;width:0%;transition:width 0.15s;"></div>' +
                  '</div>' +
                  '<span class="atk-label" style="font-size:12px;">0%</span>' +
                '</div>'
            );
        }
        return $('#' + id);
    }

    function setProgress($bar, pct) {
        $bar.find('.atk-fill').css('width', pct + '%');
        $bar.find('.atk-label').text(pct + '%');
    }

    function setStatus($bar, msg, isError) {
        $bar.find('.atk-fill').css('background', isError ? '#dc3232' : '#46b450');
        $bar.find('.atk-label').text(msg).css('color', isError ? '#dc3232' : '#46b450');
    }

    // ── Network helpers ──────────────────────────────────────────────────────

    function ajaxPost(action, data) {
        return new Promise(function (resolve, reject) {
            $.post(ajaxurl, $.extend({ action: action, nonce: adminToolkitS3.nonce }, data))
                .done(function (res) {
                    if (res.success) resolve(res.data);
                    else reject(new Error((res.data && res.data.message) || ('Error en ' + action)));
                })
                .fail(function () { reject(new Error('Error de red en ' + action)); });
        });
    }

    function putToS3(url, blob, onProgress) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('PUT', url, true);
            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable && onProgress) onProgress(e.loaded, e.total);
            };
            xhr.onload = function () {
                if (xhr.status === 200) {
                    resolve(xhr.getResponseHeader('ETag'));
                } else {
                    reject(new Error('S3 PUT falló con status ' + xhr.status));
                }
            };
            xhr.onerror = function () { reject(new Error('Error de red al subir a S3')); };
            xhr.send(blob);
        });
    }

    function withRetry(fn, retries) {
        retries = retries || MAX_RETRIES;
        return fn().catch(function (err) {
            if (retries <= 1) throw err;
            return new Promise(function (r) { setTimeout(r, 1000 * (MAX_RETRIES - retries + 1)); })
                .then(function () { return withRetry(fn, retries - 1); });
        });
    }

    // ── Upload strategies ────────────────────────────────────────────────────

    function simpleUpload(file, carpeta, $bar) {
        return ajaxPost('s3_get_simple_presigned_url', {
            filename:     file.name,
            carpeta:      carpeta,
            content_type: file.type || 'application/octet-stream'
        }).then(function (data) {
            if (data.exists) {
                setProgress($bar, 100);
                return data.key;
            }
            return putToS3(data.url, file, function (loaded, total) {
                setProgress($bar, Math.round(loaded / total * 100));
            }).then(function () { return data.key; });
        });
    }

    function multipartUpload(file, carpeta, $bar) {
        var uploadId, key;
        var totalParts = Math.ceil(file.size / PART_SIZE);

        return ajaxPost('s3_initiate_multipart', {
            filename:     file.name,
            carpeta:      carpeta,
            content_type: file.type || 'application/octet-stream'
        }).then(function (data) {
            if (data.exists) {
                setProgress($bar, 100);
                return data.key;
            }
            uploadId = data.upload_id;
            key      = data.key;

            var parts = [];
            var chain = Promise.resolve();

            for (var i = 1; i <= totalParts; i++) {
                (function (partNumber) {
                    var start = (partNumber - 1) * PART_SIZE;
                    var end   = Math.min(start + PART_SIZE, file.size);
                    var chunk = file.slice(start, end);

                    chain = chain.then(function () {
                        return withRetry(function () {
                            return ajaxPost('s3_get_presigned_url', {
                                upload_id:   uploadId,
                                key:         key,
                                part_number: partNumber
                            }).then(function (d) {
                                return putToS3(d.url, chunk, function (loaded) {
                                    var pct = Math.round(((partNumber - 1) * PART_SIZE + loaded) / file.size * 100);
                                    setProgress($bar, Math.min(pct, 99));
                                });
                            }).then(function (etag) {
                                parts.push({ PartNumber: partNumber, ETag: etag });
                            });
                        });
                    });
                }(i));
            }

            return chain.then(function () {
                return ajaxPost('s3_complete_multipart', {
                    upload_id: uploadId,
                    key:       key,
                    parts:     JSON.stringify(parts)
                });
            }).then(function () {
                return key;
            }).catch(function (err) {
                ajaxPost('s3_abort_multipart', { upload_id: uploadId, key: key }).catch(function () {});
                throw err;
            });
        });
    }

    function registerFile(postId, key, file, idioma, medidas) {
        var ext    = file.name.split('.').pop().toLowerCase();
        var pesoKb = Math.round(file.size / 1000);
        return ajaxPost('s3_register_file', {
            post_id: postId,
            key:     key,
            nombre:  file.name,
            idioma:  idioma,
            medidas: medidas,
            peso_kb: pesoKb,
            formato: ext
        });
    }

    // ── Form handler ─────────────────────────────────────────────────────────

    $(document).ready(function () {
        initProgressArea();

        // Confirm script loaded successfully
        $('#atk-s3-status').show();

        $('form#atk-files-form').on('submit', function (e) {
            e.preventDefault();

            var $form    = $(this);
            var postId   = $form.find('input[name="post_id"]').val();
            var carpeta  = adminToolkitS3.carpeta;
            var $submit  = $form.find('input[type="submit"]');
            var $newRows = $form.find('tr.atk-new-row');

            $submit.prop('disabled', true).val('Subiendo...');
            $('#atk-upload-progress').empty();

            var tasks = [];

            $newRows.each(function () {
                var $row   = $(this);
                var file   = $row.find('input[type="file"]')[0].files[0];
                if (!file) return;

                var idioma  = $row.find('select[name*="idioma"]').val();
                var medidas = $row.find('input[name*="medidas"]').val() || '';
                var $bar    = upsertProgressBar(file.name);

                var uploadFn = file.size < PART_SIZE
                    ? simpleUpload(file, carpeta, $bar)
                    : multipartUpload(file, carpeta, $bar);

                tasks.push(
                    uploadFn
                        .then(function (key) {
                            setProgress($bar, 100);
                            return registerFile(postId, key, file, idioma, medidas);
                        })
                        .then(function () {
                            setStatus($bar, 'Subido correctamente', false);
                        })
                        .catch(function (err) {
                            setStatus($bar, 'Error: ' + err.message, true);
                            throw err;
                        })
                );
            });

            Promise.allSettled(tasks).then(function (results) {
                $submit.prop('disabled', false).val('Guardar');
                var allOk = results.every(function (r) { return r.status === 'fulfilled'; });
                if (allOk && tasks.length > 0) {
                    setTimeout(function () {
                        window.location.href = adminToolkitS3.backUrl;
                    }, 1500);
                }
            });
        });
    });

}(jQuery));
