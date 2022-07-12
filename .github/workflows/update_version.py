# open file with r+b (allow write and binary mode)
import re
import sys
from pathlib import Path

txt = Path('sim-plugin.php').read_text()
newVersion  = sys.argv[1]

oldVersion = re.search(r'\* Version:[ \t]*([\d.]+)', txt).group(1)
print(oldVersion)
txt = txt.replace(oldVersion, newVersion)

print(txt)


#f_content = re.sub(r'\* Version\s*([\d.]+)', r'* Version 2', f_content)
