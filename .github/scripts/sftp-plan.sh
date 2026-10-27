#!/usr/bin/env bash
# Genera los comandos de lftp para sincronizar el plugin con preproducción.
#
# Compara la revisión ya desplegada (guardada en el servidor en .deploy-revision) con la
# actual: sube los archivos nuevos o modificados y elimina en el servidor los que se
# borraron del repo. Si no hay revisión previa válida, sube todo y no elimina nada.
#
# Uso: sftp-plan.sh <revision_desplegada|""> <revision_actual> <dir_remoto> <archivo_plan>
# Escribe el plan en <archivo_plan> y un resumen en Markdown por la salida estándar.
set -euo pipefail

prev="$1"
head="$2"
remote="${3%/}"
plan="$4"
marker="$(dirname "$plan")/deploy-revision"

# Comillas para lftp.
q() {
	local s=${1//\\/\\\\}
	s=${s//\"/\\\"}
	printf '"%s"' "$s"
}

printf '%s\n' "$head" > "$marker"
: > "$plan"

if [[ -z "$prev" ]] || ! git cat-file -e "${prev}^{commit}" 2>/dev/null; then
	echo "### Sincronización completa"
	echo
	echo "No hay una revisión previa válida en el servidor (\`${prev:-ninguna}\`): se suben todos los archivos y no se elimina nada."
	{
		echo "mirror -R --parallel=4 --exclude '^\\.git/' --exclude '^\\.github/' ./ $(q "$remote")"
		echo "put $(q "$marker") -o $(q "$remote/.deploy-revision")"
	} >> "$plan"
	exit 0
fi

changed=()
deleted=()
while IFS= read -r -d '' status && IFS= read -r -d '' path; do
	[[ "$path" == .github/* ]] && continue
	case "$status" in
		D) deleted+=("$path") ;;
		*) changed+=("$path") ;;
	esac
done < <(git -c core.quotepath=off diff --name-status --no-renames -z "$prev" "$head")

# Directorios que hay que crear antes de subir.
declare -A dirs=()
for path in "${changed[@]}"; do
	dir=$(dirname "$path")
	[[ "$dir" != "." ]] && dirs["$dir"]=1
done

# Directorios que quedan vacíos tras las eliminaciones (ya no existen en el repo).
declare -A empty_dirs=()
for path in "${deleted[@]}"; do
	dir=$(dirname "$path")
	while [[ "$dir" != "." ]]; do
		if [[ -z "$(git ls-tree -d "$head" -- "$dir")" ]]; then
			empty_dirs["$dir"]=1
		fi
		dir=$(dirname "$dir")
	done
done

{
	# Crear directorios no debe fallar si ya existen.
	echo "set cmd:fail-exit no"
	for dir in "${!dirs[@]}"; do
		echo "mkdir -p $(q "$remote/$dir")"
	done

	echo "set cmd:fail-exit yes"
	for path in "${changed[@]}"; do
		echo "put $(q "$path") -o $(q "$remote/$path")"
	done

	# Un archivo que ya no existe en el servidor no debe detener el despliegue.
	echo "set cmd:fail-exit no"
	for path in "${deleted[@]}"; do
		echo "rm $(q "$remote/$path")"
	done
	# Más profundos primero.
	for dir in "${!empty_dirs[@]}"; do
		echo "$dir"
	done | awk -F/ '{ print NF "\t" $0 }' | sort -rn | cut -f2- | while IFS= read -r dir; do
		echo "rmdir $(q "$remote/$dir")"
	done

	echo "set cmd:fail-exit yes"
	echo "put $(q "$marker") -o $(q "$remote/.deploy-revision")"
} >> "$plan"

echo "### Sincronización incremental"
echo
echo "\`${prev:0:7}\` → \`${head:0:7}\`: ${#changed[@]} archivo(s) subido(s), ${#deleted[@]} eliminado(s)."
if (( ${#changed[@]} )); then
	echo
	echo "**Subidos**"
	printf -- '- `%s`\n' "${changed[@]}"
fi
if (( ${#deleted[@]} )); then
	echo
	echo "**Eliminados**"
	printf -- '- `%s`\n' "${deleted[@]}"
fi
