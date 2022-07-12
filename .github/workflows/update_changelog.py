from cgitb import text
import re
import sys
from pathlib import Path

file    = 'CHANGELOG.md'
newLine = sys.argv[1]
lines   = newLine.split(': ', 1)
type    = lines[0].lower()
if(len(lines) == 1):
    print("ERROR: \nYou should start your commit message with either 'ADDED: ', 'CHANGED: ' or 'FIXED: '")
    exit(0)
text    = lines[1]

# load plugin file
changelog = Path(file).read_text()

total   = re.search(r'## \[Unreleased\] - yyyy-mm-dd([\s\S]*?)## \[', changelog).group(1)
if(type == 'added'):
    added       = re.search(r'(### Added[\s\S]*?)###', total).group(1)
    newAdded    = added + "\n- " + text + "\n"
    newTotal    = total.replace(added, newAdded)
elif(type == 'changed'):
    changed = re.search(r'(### Changed[\s\S]*?)###', total).group(1)
    newChanged  = changed + "\n- " + text + "\n"
    newTotal    = total.replace(changed, newChanged)
elif(type == 'fixed'):
    fixed       = re.search(r'(### Fixed[\s\S]*)', total).group(1)
    newFixed    = fixed + "\n- " + text + "\n"
    newTotal    = total.replace(fixed, newFixed)
else:
    print("ERROR: \nYou should start your commit message with either 'ADDED: ', 'CHANGED: ' or 'FIXED: '")
    exit(1)

print(changelog)
print(total)
print(newTotal)
changelog = changelog.replace(total, newTotal)

# Write changes
f = open(file, "w")
f.write(changelog)
f.close()