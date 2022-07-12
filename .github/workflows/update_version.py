from hashlib import new
import re
import sys
from pathlib import Path
import datetime

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

# Update the changelog with the new release

file    = 'CHANGELOG.md'

# load plugin file
changelog = Path(file).read_text()

unreleased   = re.search(r'(## \[Unreleased\] - yyyy-mm-dd)', changelog).group(1)

new            = "## [Unreleased] - yyyy-mm-dd\n\n### Added\n\n### Changed\n\n### Fixed\n\n## [" + newVersion + "] - " + datetime.datetime.now().strftime("%Y-%m-%d")

changelog = changelog.replace(unreleased, new)

# Write changes
f = open(file, "w")
f.write(changelog)
f.close()