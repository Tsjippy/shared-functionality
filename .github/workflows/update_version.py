import re
from pathlib import Path
import datetime
from github import Github
from github.GithubException import GithubException, UnknownObjectException
import os
import subprocess
import secrets
import re

def check_input(key: str) -> bool:
    """
    Checks if a given key was passed in as an input variable
    """
    return f'{key}' in os.environ and os.environ[f'{key}'] != ""

def run_command(cmd: list[str], end_group: bool = False):
    """
    Runs a given command, surrounding output with ::stop-commands::
    :param cmd: command to run
    :param end_group: whether to run "::endgroup::" before exiting
    """
    token = secrets.token_urlsafe(32)
    print(f"::debug::Running {cmd}")
    proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    out, _ = proc.communicate()
    print(f"::stop-commands::{token}")
    print(out.decode())
    if proc.returncode != 0:
        print(f"::{token}::")
        if end_group:
            print("::endgroup::")
        print(f"::error::❌ Command {cmd} returned with non-zero exit code!")
        exit(proc.returncode)
    print(f"::{token}::")

#
# Update the plugin file with the new version
#
def update_plugin_file():
    global tag_name
    global plugin_file_contents

    if not check_input("PLUGIN"):
        print("::error::❌ Missing required input: PLUGIN")
        exit(1)

    plugin = os.environ['PLUGIN']

    if os.path.isfile(f"tsjippy-{plugin}.php"):
        file_path   = f"tsjippy-{plugin}.php"
    else:
        file_path   = 'style.css'

    print(f"Filepath is {file_path}")

    # load plugin file
    plugin_file_contents = Path(file_path).read_text()

    # get old version
    try:
        oldVersion = re.search(r'Version:[ \t]*([\d.]+)', plugin_file_contents).group(1)

        print(f'Old version is {oldVersion}')
    except Exception as e:
        exit()

    print(f'New version is {tag_name}')

    # replace with new
    plugin_file_contents = plugin_file_contents.replace(oldVersion, tag_name)

    # Write changes
    f = open(file_path, "w")
    f.write(plugin_file_contents)
    f.close()

#
# Update the changelog with the new release
# 
# Also create the the changelog.txt for wp
#
def update_change_log():
    global latest_release_notes
    global tag_name

    file    = 'CHANGELOG.md'

    # load changelog file
    changelog = Path(file).read_text()

    # Get the whole unrelease section
    try:
        total                   = re.search(r'## \[Unreleased\] - yyyy-mm-dd([\s\S]*?)## \[', changelog).group(1)
        latest_release_notes    = total

        # Remove empty sections
        for x in ["Added", "Changed", "Fixed", "Updated"]:
            pattern = r'(### ' + x + r'[\s\S]*'

            if(x != 'Updated'):
                pattern = pattern + '?)###'
            else:
                pattern = pattern + ')'

            added   = re.search(pattern, total).group(1)

            if(added.rstrip("\n") == '### '+x):
                latest_release_notes    = latest_release_notes.replace(added, '')

        # Update in changelog
        changelog   = changelog.replace(total, latest_release_notes)
    except Exception as e:
        pass

    # Add new unreleased section
    newSection  = "## [Unreleased] - yyyy-mm-dd\n\n### Added\n\n### Changed\n\n### Fixed\n\n### Updated\n\n## [" + tag_name + "] - " + datetime.datetime.now().strftime("%Y-%m-%d")+"\n"
    changelog    = changelog.replace('## [Unreleased] - yyyy-mm-dd', newSection)

    # Write changes
    f = open(file, "w")
    f.write(changelog)
    f.close()

    #
    # changelog.txt
    #

    # Write changes
    f = open("changelog.txt", "w")
    f.write(changelog)
    f.close()

    #
    # Store main release info
    #

    # Get release notes of last minor
    major   = tag_name.split('.')[0]
    minor   = tag_name.split('.')[1]

    pattern = rf"(##\s\[{major}.{minor}.*?)##\s\[{major}."
    if minor == '0':
        pattern += minor
    else:
        pattern += str(int(minor) - 1)
    matches = re.findall(pattern, changelog, re.DOTALL)

    latest_release_notes   = matches[0]+"\n\n"

    print(latest_release_notes)
    print(matches)

    ## Get all minor releases of this major
    matches = re.findall(rf"(##\s\[{major}.\d{{1,2}}.0.*?)##\s\[", changelog, re.DOTALL)

    for match in matches:
        latest_release_notes += match+"\n\n"
    
    print(latest_release_notes)

    ## Get all major releases of this major
    matches = re.findall(rf"(##\s\[\d{{1,2}}.0.0.*?)##\s\[", changelog, re.DOTALL)

    for match in matches:
        latest_release_notes += match+"\n\n"

    print(latest_release_notes)

#
# Create a readme.txt
#
def create_readme():
    global latest_release_notes
    global plugin_file_contents

    #
    # Plugin info
    #
    info    = {}
    matches = re.findall(r"\*\s([a-zA-Z ]*):\s*(.*)", plugin_file_contents)

    for match in matches:
        info[match[0]] = match[1]

    readme = f"=== {info['Plugin Name']} ===\n"
    readme += "Contributors: tsjippy\n"
    readme += "Donate link: https://www.harmseninnigeria.nl/\n"
    try:
        readme += f"Tags: {info['tags']}\n"
    except KeyError:
        print("no tags found")

    readme += f"Requires at least: {info['Requires at least']}\n"
    readme += f"Tested up to: {info['Tested up to']}\n"
    readme += f"Stable tag: {info['Tested']}\n"
    readme += f"Requires PHP: {info['Requires PHP']}\n"
    readme += "License: GPLv2 or later\n"
    readme += "License URI: https://www.gnu.org/licenses/gpl-2.0.html\n\n"

    #
    # Add Everything from README.md
    #
    if Path("readme.md").exists():
        file_path   = 'readme.md'
    else:
        file_path   = 'README.md'

    readme  += Path(file_path).read_text()

    # 
    # Add changelog
    #
    readme  += "\n\n== Changelog ==\n"

    readme += latest_release_notes

    # Write it all
    file    = 'readme.txt'
    f = open(file, "w")
    f.write(readme)
    f.close()

# 
# Create Release or updates the description of the existing one
# Copied from https://github.com/mini-bomba/create-github-release
#
def create_release():
    # A workaround for the "dubious ownership" error
    print('::debug::😩 Attempting a workaround for the "dubious ownership" git error')
    run_command(["git", "config", "--global", "--add", "safe.directory", "/github/workspace"])

    # Create Github object
    github = Github(base_url=os.environ['GITHUB_API_URL'],
                    login_or_token=os.environ['GITHUB_TOKEN'],
                    user_agent="mini-bomba/create-github-release")

    # Get the repo
    repo = github.get_repo(os.environ['GITHUB_REPOSITORY'])

    # Check current release state
    print("👀 Checking current state of the release")
    release = None
    try:
        release = repo.get_release(tag_name)
    except UnknownObjectException:
        release = None

    if release is not None:
        print("👌 Release found, copying missing input data")
    else:
        print("❗ Release does not exists (yet)")
        if latest_release_notes is None:
            print("::error::Input parameter 'latest_release_notes' must be passed if the release does not exist")
            exit(1)

    if release is not None:
        print("📝 Updating data...")
        release.update_release(tag_name, latest_release_notes)
    else:
        print("📝 Creating new release...")
        release = repo.create_git_release(tag_name, tag_name, latest_release_notes)
    print("::endgroup::")
    print("👌😎 Release created!")

# Read inputs & put them into variables
if not check_input("GITHUB_TOKEN"):
    print("::error::❌ Missing required input: GITHUB_TOKEN")
    exit(1)
token = os.environ['GITHUB_TOKEN']

if not check_input("RELEASE_TAG"):
    print("::error::❌ Missing required input: RELEASE_TAG")
    exit(1)
tag_name = os.environ['RELEASE_TAG']

latest_release_notes        = None
plugin_file_contents        = None


update_plugin_file()

update_change_log()

create_readme()

create_release()
