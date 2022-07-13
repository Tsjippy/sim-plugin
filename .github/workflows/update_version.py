import re
import sys
from pathlib import Path
import datetime

# load plugin file
txt = Path('sim-plugin.php').read_text()
newVersion  = sys.argv[1]

# get old version
oldVersion = re.search(r'\* Version:[ \t]*([\d.]+)', txt).group(1)

# replace with new
txt = txt.replace(oldVersion, newVersion)

# Write changes
f = open('sim-plugin.php', "w")
f.write(txt)
f.close()

# Update the changelog with the new release

file    = 'CHANGELOG.md'

# load plugin file
changelog = Path(file).read_text()

# Get the whole unrelease section
total       = re.search(r'## \[Unreleased\] - yyyy-mm-dd([\s\S]*?)## \[', changelog).group(1)
newTotal    = total

# Remove emty sections
for x in ["Added", "Changed", "Fixed"]:
    pattern = r'(### '+x+'[\s\S]*'

    if(x != 'Fixed'):
        pattern = pattern+'?)###'
    else:
        pattern = pattern+')'

    added   = re.search(pattern, total).group(1)

    if(added.rstrip("\n") == '### '+x):
        newTotal    = newTotal.replace(added, '')

# Update in changelog
changelog   = changelog.replace(total, newTotal)

# Add new unreleased section
newSection  = "## [Unreleased] - yyyy-mm-dd\n\n### Added\n\n### Changed\n\n### Fixed\n\n## [" + newVersion + "] - " + datetime.datetime.now().strftime("%Y-%m-%d")+"\n"
changelog    = changelog.replace('## [Unreleased] - yyyy-mm-dd', newSection)

print("Writing new changelog")
print(changelog)
# Write changes
""" f = open(file, "w")
f.write(changelog)
f.close() """