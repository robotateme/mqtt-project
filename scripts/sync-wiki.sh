#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WIKI_DIR="${WIKI_DIR:-$ROOT_DIR/.wiki}"
PUSH="${PUSH:-0}"

GITHUB_WIKI_URL="${GITHUB_WIKI_URL:-git@github.com:robotateme/mqtt-project.wiki.git}"
GITLAB_WIKI_URL="${GITLAB_WIKI_URL:-git@gitlab.com:robotateme/mqtt-project.wiki.git}"

pages=(
  "README.md:Home.md"
  "docs/architecture.md:Architecture.md"
  "docs/infrastructure.md:Infrastructure.md"
  "docs/rfc-architecture.md:RFC-Architecture.md"
  "docs/makefile.md:Makefile.md"
  "docs/bus.md:Bus.md"
  "docs/core.md:Core.md"
  "frontend/README.md:Frontend.md"
  "docs/validation.md:Validation.md"
  "docs/notes.md:Notes.md"
  "docs/work-reports.md:Work-reports.md"
  "scripts/README.md:Scripts.md"
)

if [ ! -d "$WIKI_DIR/.git" ]; then
  if ! git clone "$GITHUB_WIKI_URL" "$WIKI_DIR"; then
    rm -rf "$WIKI_DIR"
    echo "GitHub wiki is not available, cloning GitLab wiki instead." >&2
    git clone "$GITLAB_WIKI_URL" "$WIKI_DIR"
  fi
fi

if git -C "$WIKI_DIR" remote get-url origin >/dev/null 2>&1; then
  git -C "$WIKI_DIR" remote set-url origin "$GITHUB_WIKI_URL"
else
  git -C "$WIKI_DIR" remote add origin "$GITHUB_WIKI_URL"
fi
if git -C "$WIKI_DIR" remote get-url gitlab >/dev/null 2>&1; then
  git -C "$WIKI_DIR" remote set-url gitlab "$GITLAB_WIKI_URL"
else
  git -C "$WIKI_DIR" remote add gitlab "$GITLAB_WIKI_URL"
fi

find "$WIKI_DIR" -maxdepth 1 -type f -name '*.md' -delete
mkdir -p "$WIKI_DIR/assets"
rm -rf "$WIKI_DIR/assets"/*

for page in "${pages[@]}"; do
  src="${page%%:*}"
  dst="${page##*:}"
  cp "$ROOT_DIR/$src" "$WIKI_DIR/$dst"
done

if [ -d "$ROOT_DIR/docs/assets" ]; then
  cp -R "$ROOT_DIR/docs/assets/." "$WIKI_DIR/assets/"
fi
cp "$ROOT_DIR/docs/architecture.puml" "$WIKI_DIR/architecture.puml"

rewrite_links() {
  local file="$1"

  perl -0 -pi \
    -e 's#\]\(docs/architecture\.md\)#](Architecture)#g;' \
    -e 's#\]\(docs/infrastructure\.md\)#](Infrastructure)#g;' \
    -e 's#\]\(docs/rfc-architecture\.md\)#](RFC-Architecture)#g;' \
    -e 's#\]\(rfc-architecture\.md\)#](RFC-Architecture)#g;' \
    -e 's#\]\(docs/makefile\.md\)#](Makefile)#g;' \
    -e 's#\]\(docs/bus\.md\)#](Bus)#g;' \
    -e 's#\]\(docs/core\.md\)#](Core)#g;' \
    -e 's#\]\(frontend/README\.md\)#](Frontend)#g;' \
    -e 's#\]\(docs/validation\.md\)#](Validation)#g;' \
    -e 's#\]\(docs/notes\.md\)#](Notes)#g;' \
    -e 's#\]\(docs/work-reports\.md\)#](Work-reports)#g;' \
    -e 's#\]\(scripts/README\.md\)#](Scripts)#g;' \
    -e 's#\]\(docs/assets/#](assets/#g;' \
    "$file"
}

find "$WIKI_DIR" -maxdepth 1 -type f -name '*.md' -print0 |
  while IFS= read -r -d '' file; do
    rewrite_links "$file"
  done

cat > "$WIKI_DIR/_Sidebar.md" <<'EOF'
- [Home](Home)
- [Architecture](Architecture)
- [Infrastructure](Infrastructure)
- [RFC Architecture](RFC-Architecture)
- [Makefile](Makefile)
- [Bus](Bus)
- [Core](Core)
- [Frontend](Frontend)
- [Validation](Validation)
- [Notes](Notes)
- [Work reports](Work-reports)
- [Scripts](Scripts)
EOF

git -C "$WIKI_DIR" add -A

if git -C "$WIKI_DIR" diff --cached --quiet; then
  echo "Wiki is already up to date."
  exit 0
fi

git -C "$WIKI_DIR" commit -m "Синхронизировал wiki с документацией"

if [ "$PUSH" = "1" ]; then
  git -C "$WIKI_DIR" push origin HEAD:master || \
    echo "Unable to push GitHub wiki. Check that GitHub wiki is enabled." >&2
  git -C "$WIKI_DIR" push gitlab HEAD:master
fi
