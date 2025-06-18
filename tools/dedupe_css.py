import sys, re, pathlib, collections

folder = pathlib.Path(sys.argv[1]) if len(sys.argv)>1 else pathlib.Path('assets/css')
selector_map = collections.defaultdict(list)
for path in folder.rglob('*.css'):
    if 'bootstrap' in path.name:
        continue
    for line in path.read_text().splitlines():
        m = re.match(r'(\.[\w\-]+)\s*\{', line)
        if m:
            selector_map[m.group(1)].append(path)

for sel, files in selector_map.items():
    if len(files) > 1:
        print(sel, '->', ', '.join(str(p) for p in files))
