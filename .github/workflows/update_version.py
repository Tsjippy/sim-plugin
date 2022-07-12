# open file with r+b (allow write and binary mode)
import re
import sys
from pathlib import Path

txt = Path('sim-plugin.php').read_text()
newVersion  = sys.argv[1]

oldVersion = re.search(r'\* Version:[ \t]*([\d.]+)', txt).group(1)
txt = txt.replace(oldVersion, newVersion)

f = open('sim-plugin.php', "w")
f.write(txt)
f.close()