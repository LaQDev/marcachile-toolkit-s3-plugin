<?php
wp_enqueue_script( 'highcharts', 'https://code.highcharts.com/highcharts.js', [], '11', true );
wp_enqueue_script( 'highcharts-more', 'https://code.highcharts.com/highcharts-more.js', ['highcharts'], '11', true );
?>

<style>

.wrapper {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    grid-gap: 10px;
    grid-column-gap: 1em;
    margin-top: 1em;
}

.column {
    grid-row: 1;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
    background: #fff;
}

</style>

<?php

global $wpdb;
$total_usuarios = $wpdb->get_results("SELECT count(id_usuario) AS total FROM toolkit_users", 'ARRAY_A');
$total_descargas = $wpdb->get_results("SELECT count(id_descarga) AS total FROM toolkit_descargas", 'ARRAY_A');
$data_usuarios = $wpdb->get_results("SELECT YEAR(fecha_registro) AS y, MONTH(fecha_registro) AS m, COUNT(id_usuario) AS users FROM toolkit_users GROUP BY YEAR(fecha_registro), MONTH(fecha_registro) ORDER BY 1 DESC, 2 DESC LIMIT 6", 'ARRAY_A');
$data_descargas = $wpdb->get_results("SELECT YEAR(fecha_registro) AS y, MONTH(fecha_registro) AS m, COUNT(id_descarga) AS users FROM toolkit_descargas GROUP BY YEAR(fecha_registro), MONTH(fecha_registro) ORDER BY 1 DESC, 2 DESC LIMIT 6", 'ARRAY_A');
$data_descargas_cat = $wpdb->get_results("SELECT t.name AS category, COUNT(tda.id_archivo) AS downloads
										FROM toolkit_descargas_archivos tda
										JOIN wp_term_relationships ttr ON ttr.object_id = tda.archivo
										JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = ttr.term_taxonomy_id AND tt.taxonomy = 'categorias'
										JOIN wp_terms t ON (t.term_id = tt.term_id)
										GROUP BY t.name", 'ARRAY_A');

$data_descargas_cat_mes = $wpdb->get_results("SELECT YEAR(tda.fecha_registro) AS y, MONTH(tda.fecha_registro) AS m, t.name AS category, COUNT(tda.id_archivo) AS downloads
												FROM toolkit_descargas_archivos tda
												JOIN wp_term_relationships ttr ON ttr.object_id = tda.archivo
												JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = ttr.term_taxonomy_id AND tt.taxonomy = 'categorias'
												JOIN wp_terms t ON (t.term_id = tt.term_id)
												GROUP BY YEAR(tda.fecha_registro), MONTH(tda.fecha_registro), t.name ORDER BY 1 DESC, 2 DESC", 'ARRAY_A');

// var_dump($data_descargas_cat_mes);
// die;

$result_usuarios = [];
$result_descargas = [];
$result_descargas_cat = [];

foreach ($data_usuarios as $row) {
    $result_usuarios[] = [(int) $row["y"], (int) $row["m"], (int) $row["users"]];    
}

foreach ($data_descargas as $row) {
    $result_descargas[] = [(int) $row["y"], (int) $row["m"], (int) $row["users"]];    
}

foreach ($data_descargas_cat as $row) {
    $result_descargas_cat[] = ["name" => $row["category"], "y" => (int) $row["downloads"]];    
}

?>

<div class="wrap">
    <h1>Dashboards</h1>
    <div class="wrapper">
        <div class="column">
            <h3 style="display:inline-block; width: 70%; padding-left: 2em;">Total de usuarios:</h3>
            <p style="display:inline-block; font-size: 16px;"><?= $total_usuarios[0]['total']; ?></p>
        </div>
    </div>
    <div class="wrapper">
        <div class="column">
            <div id="container" style="width:100%; height:400px;"></div>
        </div>
    </div>
    <div class="wrapper">
        <div class="column">
            <h3 style="display:inline-block; width: 70%; padding-left: 2em;">Total de descargas:</h3>
            <p style="display:inline-block; font-size: 16px;"><?= $total_descargas[0]['total']; ?></p>
        </div>
    </div>
    <div class="wrapper">
        <div class="column">
            <div id="container_descargas" style="width:100%; height:400px;"></div>
        </div>
        <div class="column">
            <div id="container_descargas_cat" style="width:100%; height:400px;"></div>
        </div>
    </div>
    <div class="wrapper">
        <div class="column">
            <div id="container_descargas_mes" style="width:100%; height:400px;"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var data_usuarios = JSON.parse('<?= json_encode($result_usuarios); ?>');
        var data_descargas = JSON.parse('<?= json_encode($result_descargas); ?>');
        var data_descargas_cat = JSON.parse('<?= json_encode($result_descargas_cat); ?>');
        		
        const chart = Highcharts.chart('container', {
			chart: {
				type: "spline"
			},
			title: {
				text: "Nuevos Usuarios por Mes"
			},
			xAxis: {
				type: 'datetime',
		        title: {
		            text: 'Mes'
		        }
			},
			yAxis: {
				title: {
					text: "Usuarios"
				},
				min: 0
			},
		    tooltip: {
		        headerFormat: '<b>{series.name}</b><br>',
		        pointFormat: '{point.x:%b}: {point.y}'
		    },		
		    plotOptions: {
		        series: {
		            marker: {
		                enabled: true
		            }
		        }
		    },
			credits: {
				enabled: false
			},
			legend: {
				enabled: false
			},
            series: [{
                name: "Usuarios",
                lineWidth: 4,
                marker: {
                    radius: 4,
                },
                data: data_usuarios.map(row => {
                    return [Date.UTC(row[0], row[1] - 1, 1), row[2]];
                })
            }]
        });
        
        const chart2 = Highcharts.chart('container_descargas', {
			chart: {
				type: "spline"
			},
			title: {
				text: "Descargas Mensuales"
			},
			xAxis: {
				type: 'datetime',
		        title: {
		            text: 'Mes'
		        }
			},
			yAxis: {
				title: {
					text: "Descargas"
				},
				min: 0
			},
		    tooltip: {
		        headerFormat: '<b>{series.name}</b><br>',
		        pointFormat: '{point.x:%b}: {point.y}'
		    },		
		    plotOptions: {
		        series: {
		            marker: {
		                enabled: true
		            }
		        }
		    },
			credits: {
				enabled: false
			},
			legend: {
				enabled: false
			},
            series: [{
                name: "Descargas",
                lineWidth: 4,
                marker: {
                    radius: 4,
                },
                data: data_descargas.map(row => {
                    return [Date.UTC(row[0], row[1] - 1, 1), row[2]];
                })
            }]
        });
        
        const chart3 = Highcharts.chart('container_descargas_cat', {
			chart: {
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false,
				type: 'pie'
			},
			title: {
				text: 'Descargas totales por categoría'
			},
			tooltip: {
				pointFormat: '<b>{point.y}</b>'
			},
			accessibility: {
				point: {
					valueSuffix: '%'
				}
			},
			plotOptions: {
				pie: {
					allowPointSelect: true,
					cursor: 'pointer',
					dataLabels: {
						enabled: true,
						format: '<b>{point.name}</b>: {point.percentage:.1f} %'
					}
				}
			},
			series: [{
				colorByPoint: true,
				data: data_descargas_cat
			}]
        });
        
        const chart4 = Highcharts.chart('container_descargas_mes', {
			chart: {
				type: 'column'
			},
			title: {
				text: 'Descargas mensuales por categoría'
			},
			xAxis: {
				type: 'datetime',
				categories: ['May 21', 'Jun 21', 'Jul 21', 'Aug 21', 'Sep 21', 'Oct 21'],
		        title: {
		            text: 'Mes'
		        }
			},
			yAxis: {
				min: 0,
				title: {
					text: 'Descargas'
				},
				stackLabels: {
					enabled: true,
					style: {
						fontWeight: 'bold',
						color: ( // theme
							Highcharts.defaultOptions.title.style &&
							Highcharts.defaultOptions.title.style.color
						) || 'gray'
					}
				}
			},
			tooltip: {
				headerFormat: '<b>{point.x}</b><br/>',
				pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
			},
			plotOptions: {
				column: {
					stacking: 'normal',
					dataLabels: {
						enabled: true
					}
				}
			},
			series: [{
				name: 'John',
				data: [5, 3, 4, 7, 2, 2]
			}, {
				name: 'Jane',
				data: [2, 2, 3, 2, 1, 3]
			}, {
				name: 'Joe',
				data: [3, 4, 4, 2, 5, 4]
			}]
        });
    });

</script>