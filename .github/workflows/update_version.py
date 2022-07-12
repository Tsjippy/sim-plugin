import re
import sys
from pathlib import Path

# load plugin file
txt = Path('sim-plugin.php').read_text()
newVersion  = sys.argv[1]

#get old version
oldVersion = re.search(r'\* Version:[ \t]*([\d.]+)', txt).group(1)

# replcae with new
txt = txt.replace(oldVersion, newVersion)

# Write changes
f = open('sim-plugin.php', "w")
f.write(txt)
f.close()