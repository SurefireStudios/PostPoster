#!/usr/bin/env python3
"""Fail if debug leftovers reach shipped code.

  - no console.log() anywhere in assets/*.js
  - any error_log() in shipped PHP must sit inside a WP_DEBUG guard

examples/ is excluded: it is reference material for developers, not shipped behaviour.
Run from the repository root:  python3 .github/scripts/check-debug-leftovers.py
"""
import pathlib
import sys

ROOT = pathlib.Path(__file__).resolve().parents[2]
SKIP_DIRS = {".git", "examples", ".github"}

problems = []

for path in sorted((ROOT / "assets").glob("*.js")):
    for lineno, line in enumerate(path.read_text(encoding="utf-8").splitlines(), 1):
        if "console.log(" in line:
            problems.append(
                "%s:%d  console.log in shipped JavaScript"
                % (path.relative_to(ROOT).as_posix(), lineno)
            )

for path in sorted(ROOT.rglob("*.php")):
    rel = path.relative_to(ROOT)
    if rel.parts[0] in SKIP_DIRS:
        continue
    lines = path.read_text(encoding="utf-8").splitlines()
    for lineno, line in enumerate(lines, 1):
        if "error_log(" in line:
            context = "\n".join(lines[max(0, lineno - 5):lineno])
            if "WP_DEBUG" not in context:
                problems.append(
                    "%s:%d  error_log without a WP_DEBUG guard"
                    % (rel.as_posix(), lineno)
                )

for problem in problems:
    print("::error::" + problem)

print("Debug-leftover check: %d issue(s) found." % len(problems))
sys.exit(1 if problems else 0)
