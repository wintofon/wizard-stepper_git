import sys, re, glob
from collections import defaultdict
selectors = defaultdict(list)
for path in glob.glob('assets/css/**/*.css', recursive=True):
    with open(path) as f:
        content = f.read()
    for m in re.finditer(r'(\.[\w-]+)\s*{([^}]*)}', content):
        selector = m.group(1)
        body = re.sub(r'\s+', ' ', m.group(2).strip())
        selectors[selector].append((path, body))
for sel, items in selectors.items():
    if len(items) > 1:
        print(sel)
        for path, body in items:
            print('  ', path, body)
        print()
