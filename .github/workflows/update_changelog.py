from cgitb import text
import re
import sys
from pathlib import Path

file    = 'CHANGELOG.md'
newLine = sys.argv[1]
newLine.split(' - ', 1)
type    = newLine[0]
text    = newLine[1]

print(newLine)
print(text)

# load plugin file
changelog = Path(file).read_text()

total   = re.search(r'## \[Unreleased\] - yyyy-mm-dd([\s\S]*?)## \[', changelog).group(1)
if(type == 'ADDED'):
    added       = re.search(r'### Added([\s\S]*?)###', total).group(1)
    newAdded    = added+text
    newTotal    = total.replace(added, newAdded)
elif(type == 'CHANGED'):
    changed = re.search(r'### Changed([\s\S]*?)###', total).group(1)
    newChanged  = changed+text
    newTotal    = total.replace(changed, newChanged)
elif(type == 'FIXED'):
    fixed       = re.search(r'### Fixed([\s\S]*)', total).group(1)
    newFixed    = fixed+text
    newTotal    = total.replace(fixed, newFixed)
else:
    print("ERROR: \nYou should start your commit message with either 'ADDED - ', 'CHANGED - ' or 'FIXED - '")
    exit(1)

changelog = changelog.replace(total, newTotal)

# Write changes
f = open(file, "w")
f.write(changelog)
f.close()