import re, sys, json
import xml.etree.ElementTree as ET

# fetch type of upgrade code from bash arguments
# 0 is Major
# 1 is Minor
# 2 is Patch
type_of_upgrade = str(sys.argv[1])

# fetch commit message from bash arguments
change_log_update = str(sys.argv[2])

# roo_path for hidden files
root_path = './.github/'


# version_file_name dictates where the version file is located that serves as
# placeholder for all versioning
version_file_name = root_path + 'version.txt'

# changelog_file_name dictates where the changelog file is located
changelog_file_name = 'changelog.md'

# changelog_file_name dictates where the changelog file is located
branch_changelog_file_name = root_path + 'branch_changelog.md'

# xml_file_name dictates where XML file is located
xml_file_name = './etc/config.xml'

# json_file_name dictates where XML file is located
json_file_name = 'composer.json'

# updateMajorVersion(Lines) updates the Major version x.0.0
def updateMajorVersion(Lines) :

    updateRegexString = '^[0-9]+'

    updateRegex = re.compile(updateRegexString)

    versionUpdate = ''
    versionStable = ''

    for line in Lines:
        versionUpdate = updateRegex.search(line).group()
                    
    versionUpdate = int(versionUpdate)
    versionUpdate = versionUpdate + 1

    version = str(versionUpdate) + '.0.0'
    return version

# updateMinorVersion(Lines) updates the minor version 0.x.0
def updateMinorVersion(Lines) :

    majorVersionString = ''
    minorVersionString = ''
    patchVersionString = ''

    section = 0

    for line in Lines:
        for c in line:
            if c == '.' :
                section = section + 1
                continue
            if section == 0 :
                majorVersionString = majorVersionString + c
            elif section == 1 :
                minorVersionString = minorVersionString + c
            elif section == 2 :
                patchVersionString = patchVersionString + c
    
    minorVersionString = int(minorVersionString)
    minorVersionString = minorVersionString + 1

    version = majorVersionString + '.' + str(minorVersionString) + '.0'
    return version

# updatePatchVersion(Lines) updates the Patch version 0.0.x
def updatePatchVersion(Lines) :

    stableRegexString = '[0-9]+\.[0-9]+\.'
    updateRegexString = '[0-9]+$'

    stableRegex = re.compile(stableRegexString)
    updateRegex = re.compile(updateRegexString)

    versionUpdate = ''
    versionStable = ''

    for line in Lines:
        versionUpdate = updateRegex.search(line).group()
        versionStable = stableRegex.search(line).group()
                    
    versionUpdate = int(versionUpdate)
    versionUpdate = versionUpdate + 1

    version = versionStable + str(versionUpdate)
    return version

# updateChangelog(version, lines) updates the changelog with latest commits and some proper formatting
def updateChangelog(version, new_lines, changelog_lines, type_of_upgrade):
    changelog_lines[2] = '## version: ' + version + '\n'

    version_split1 = changelog_lines[:7]
    version_split2 = changelog_lines[7:]
    version_line = '**' + version + '**'
    version_split1.append('\n')
    version_split1.append('\n')
    version_split1.append(version_line)
    version_split1.append('\n')
    version_split1.append('\n')

    changelog_lines = version_split1 + version_split2

    lines_part1 = changelog_lines[:12]
    lines_part2 = changelog_lines[12:]

    updatedLines = ''
    new_lines.append('\n')
    new_lines = ''.join(new_lines)

    type_of_upgrade = int(type_of_upgrade)

    if (type_of_upgrade == 0) :
        lines_part1.append(new_lines)
        lines_part1.append('\n')
        lines_part1.append('---')
        lines_part1.append('\n')
    else :
        lines_part1.append(new_lines)
        lines_part1.append('\n')

    updatedLines = lines_part1 + lines_part2
    
    return updatedLines

# update_branch_changelog(new_lines) updates the branch changelog changelog with latest commits and some formatting
def update_branch_changelog(new_lines, change_log_update) :
    lines = []
    if not new_lines :
        new_lines = []
        new_lines.append('\n')
    else :
        new_lines.insert(0, '\n')
        
    lines.append(change_log_update)
    lines.append('\n')
    return lines + new_lines

# processXML(versiion) takes version input and writes to the config.xml
def processXML(version):
    # read XML file and set root to root variable
    tree = ET.parse(xml_file_name)
    root = tree.getroot()

    # find version tag and set the version to proper version
    for version_tag in root.iter('tag'):
        version_tag.text = version

    # write data back to file
    tree.write(xml_file_name)

# processJSON(versiion) takes version input and writes to the config.xml
def processJSON(version):
    # read JSON file
    j_file = open('composer.json')

    j_obj = json.load(j_file)

    j_obj['version'] = version

    j_obj = json.dumps(j_obj, indent=4)

    with open(json_file_name, 'w') as outfile:
        outfile.write(j_obj)

# writeToFile(version) takes in version from update functions and writes it to the version file
def writeToFile(contents, filename) :
    file1 = open(filename, 'w')
    file1.writelines(contents)
    file1.close()

# readFile() reads file for use on update functions
def readFile(filename) :
    file1 = open(filename, 'r')
    Lines = file1.readlines()
    file1.close()
    return Lines

# Execution steps for updating branch specific changelog - Steps below
# 1. if type_of_upgrade is 'branch' we will:
# 2. Read the file and acquire lines from branch_changelog.md
# 3. Append the commit messages into branch changelog

if (type_of_upgrade == 'branch') :
    new_lines = readFile(branch_changelog_file_name)
    new_lines = update_branch_changelog(new_lines, change_log_update)
    writeToFile(new_lines, branch_changelog_file_name)

else :

    # Execution steps for updating version functionality - Steps below
    # 1. Read the file and acquire the lines
    # 2. Insert merge commit message
    # 3. Determine type of upgrade that needs to be procesed
    # 4. Process the versioning update
    # 5. Write the update to the file
    version_file_lines = readFile(version_file_name)

    type_of_upgrade = int(type_of_upgrade)

    # step added to read merge message
    new_lines = readFile(branch_changelog_file_name)
    new_lines = update_branch_changelog(new_lines, change_log_update)
    writeToFile(new_lines, branch_changelog_file_name)

    if type_of_upgrade == 0 :
        version_to_write = updateMajorVersion(version_file_lines)
    elif type_of_upgrade == 1 :
        version_to_write = updateMinorVersion(version_file_lines)
    elif type_of_upgrade == 2 :
        version_to_write = updatePatchVersion(version_file_lines)

    writeToFile(version_to_write, version_file_name)

    # Extecution steps for updating changelog functionality - Steps below
    # 1. Read the master changelog file and acquire the lines
    # 2. Read the branch changelog file
    # 3. Update the changelog with the proper formatting
    # 4. Write the update to the changelog file
    # 5. Clear the branch changelog file
    # 6. Update the XML version
    # 7. Update the composer.json version

    changelog_file_lines = readFile(changelog_file_name)

    branch_changelog_lines = readFile(branch_changelog_file_name)

    updated_changelog_file_lines = updateChangelog(version_to_write, branch_changelog_lines, changelog_file_lines, type_of_upgrade)

    writeToFile(updated_changelog_file_lines, changelog_file_name)

    writeToFile([], branch_changelog_file_name)

    processXML(version_to_write)

    # 08/22/2024 - Commented out ProcessJSON() due to issues with Packagist
    # processJSON(version_to_write)

