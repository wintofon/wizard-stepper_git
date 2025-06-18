import os, cssutils
from collections import defaultdict

root='assets/css'
dups=defaultdict(list)
for dp,_,fs in os.walk(root):
    for f in fs:
        if f.endswith('.css') and not f.endswith('.min.css'):
            p=os.path.join(dp,f)
            try:
                sheet=cssutils.parseFile(p)
            except Exception:
                continue
            for rule in sheet:
                if rule.type!=rule.STYLE_RULE:
                    continue
                sel=rule.selectorText
                props=tuple(sorted((prop.name.strip(),prop.value.strip()) for prop in rule.style))
                key=(sel,props)
                dups[key].append(p)

for (sel,props),files in dups.items():
    if len(files)>1:
        print(sel,'->',files)
