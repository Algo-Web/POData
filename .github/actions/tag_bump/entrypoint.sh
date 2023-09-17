#!/bin/bash

set -eo pipefail

# config
#Which type of bump to use when none explicitly provided (default: minor).
default_semvar_bump=${DEFAULT_BUMP:-minor}
# Overwrite the default branch its read from Github Runner env var but can be overwritten (default: $GITHUB_BASE_REF).
# Strongly recommended to set this var if using anything else than master or main as default branch otherwise in
# combination with history full will error.
default_branch=${DEFAULT_BRANCH:-$GITHUB_BASE_REF} # get the default branch from github runner env vars
#  Tag version with v character.
with_v=${WITH_V:-false}
# Comma separated list of branches (bash reg exp accepted) that will generate the release tags. Other branches and
# pull-requests generate versions postfixed with the commit hash and do not generate any tag.
# Examples: master or .* or release.*,hotfix.*,master ...
release_branches=${RELEASE_BRANCHES:-master,main}
# Set a custom tag, useful when generating tag based
# Setting this tag will invalidate any other settings set!
custom_tag=${CUSTOM_TAG:-}
# Operate on a relative path under $GITHUB_WORKSPACE.
source=${SOURCE:-.}
# Determine the next version without tagging the branch. The workflow can use the outputs new_tag and tag in
# subsequent steps. Possible values are true and false (default).
dryrun=${DRY_RUN:-false}
# Set if using git cli or git api calls for tag push operations. Possible values are false and true (default).
git_api_tagging=${GIT_API_TAGGING:-true}
# Set initial version before bump. Default 0.0.0. MAKE SURE NOT TO USE vX.X.X here if combined WITH_V
initial_version=${INITIAL_VERSION:-0.0.0}
# Set the context of the previous tag. Possible values are repo (default) or branch.
tag_context=${TAG_CONTEXT:-repo}
# Define if workflow runs in prerelease mode, false by default. Note this will be overwritten if using complex
# suffix release branches. Use it with checkout ref: ${{ github.sha }}
#     - uses: actions/checkout@v3
#        with:
#          ref: ${{ github.sha }}
#          fetch-depth: 0
prerelease=${PRERELEASE:-false}
# Suffix for your prerelease versions, beta by default. Note this will only be used if a prerelease branch.
suffix=${PRERELEASE_SUFFIX:-beta}
# Print git logs. For some projects these logs may be very large. Possible values are true (default) and false.
verbose=${VERBOSE:-false}
# Change the default #major commit message string tag.
major_string_token=${MAJOR_STRING_TOKEN:-#major}
# Change the default #minor commit message string tag.
minor_string_token=${MINOR_STRING_TOKEN:-#minor}
# Change the default #patch commit message string tag.
patch_string_token=${PATCH_STRING_TOKEN:-#patch}
#  Change the default #none commit message string tag.
none_string_token=${NONE_STRING_TOKEN:-#none}
# full: attempt to show all history, does not work on rebase and squash due missing HEAD [should be deprecated in v2]
#last: show the single last commit
#compare: show all commits since previous repo tag number
branch_history=${BRANCH_HISTORY:-compare}
# since https://github.blog/2022-04-12-git-security-vulnerability-announced/ runner uses?
git config --global --add safe.directory /github/workspace

cd "${GITHUB_WORKSPACE}/${source}" || exit 1

echo "*** CONFIGURATION ***"
echo -e "\tDEFAULT_BUMP: ${default_semvar_bump}"
echo -e "\tDEFAULT_BRANCH: ${default_branch}"
echo -e "\tWITH_V: ${with_v}"
echo -e "\tRELEASE_BRANCHES: ${release_branches}"
echo -e "\tCUSTOM_TAG: ${custom_tag}"
echo -e "\tSOURCE: ${source}"
echo -e "\tDRY_RUN: ${dryrun}"
echo -e "\tGIT_API_TAGGING: ${git_api_tagging}"
echo -e "\tINITIAL_VERSION: ${initial_version}"
echo -e "\tTAG_CONTEXT: ${tag_context}"
echo -e "\tPRERELEASE: ${prerelease}"
echo -e "\tPRERELEASE_SUFFIX: ${suffix}"
echo -e "\tVERBOSE: ${verbose}"
echo -e "\tMAJOR_STRING_TOKEN: ${major_string_token}"
echo -e "\tMINOR_STRING_TOKEN: ${minor_string_token}"
echo -e "\tPATCH_STRING_TOKEN: ${patch_string_token}"
echo -e "\tNONE_STRING_TOKEN: ${none_string_token}"
echo -e "\tBRANCH_HISTORY: ${branch_history}"

# verbose, show everything
if $verbose
then
    set -x
fi

setOutput() {
    echo "${1}=${2}" >> "${GITHUB_OUTPUT}"
}

current_branch=$(git rev-parse --abbrev-ref HEAD)

pre_release="$prerelease"
IFS=',' read -ra branch <<< "$release_branches"
for b in "${branch[@]}"; do
    # check if ${current_branch} is in ${release_branches} | exact branch match
    if [[ "$current_branch" == "$b" ]]
    then
        pre_release="false"
    fi
    # verify non specific branch names like  .* release/* if wildcard filter then =~
    if [ "$b" != "${b//[\[\]|.? +*]/}" ] && [[ "$current_branch" =~ $b ]]
    then
        pre_release="false"
    fi
done
echo "pre_release = $pre_release"

# fetch tags
git fetch --tags

tagFmt="^v?[0-9]+\.[0-9]+\.[0-9]+$"
preTagFmt="^v?[0-9]+\.[0-9]+\.[0-9]+(-$suffix\.[0-9]+)$"

# get the git refs
git_refs=
case "$tag_context" in
    *repo*)
        git_refs=$(git for-each-ref --sort=-v:refname --format '%(refname:lstrip=2)')
        ;;
    *branch*)
        git_refs=$(git tag --list --merged HEAD --sort=-committerdate)
        ;;
    * ) echo "Unrecognised context"
        exit 1;;
esac

# get the latest tag that looks like a semver (with or without v)
matching_tag_refs=$( (grep -E "$tagFmt" <<< "$git_refs") || true)
matching_pre_tag_refs=$( (grep -E "$preTagFmt" <<< "$git_refs") || true)
tag=$(head -n 1 <<< "$matching_tag_refs")
pre_tag=$(head -n 1 <<< "$matching_pre_tag_refs")

# if there are none, start tags at INITIAL_VERSION
if [ -z "$tag" ]
then
    if $with_v
    then
        tag="v$initial_version"
    else
        tag="$initial_version"
    fi
    if [ -z "$pre_tag" ] && $pre_release
    then
        if $with_v
        then
            pre_tag="v$initial_version"
        else
            pre_tag="$initial_version"
        fi
    fi
fi

# get current commit hash for tag
tag_commit=$(git rev-list -n 1 "$tag" || true )
# get current commit hash
commit=$(git rev-parse HEAD)
# skip if there are no new commits for non-pre_release
if [ "$tag_commit" == "$commit" ]
then
    echo "No new commits since previous tag. Skipping..."
    setOutput "new_tag" "$tag"
    setOutput "tag" "$tag"
    exit 0
fi

# sanitize that the default_branch is set (via env var when running on PRs) else find it natively
if [ -z "${default_branch}" ] && [ "$branch_history" == "full" ]
then
    echo "The DEFAULT_BRANCH should be autodetected when tag-action runs on on PRs else must be defined, See: https://github.com/anothrNick/github-tag-action/pull/230, since is not defined we find it natively"
    default_branch=$(git branch -rl '*/master' '*/main' | cut -d / -f2)
    echo "default_branch=${default_branch}"
    # re check this
    if [ -z "${default_branch}" ]
    then
        echo "::error::DEFAULT_BRANCH must not be null, something has gone wrong."
        exit 1
    fi
fi

# get the merge commit message looking for #bumps
declare -A history_type=(
    ["last"]="$(git show -s --format=%B)" \
    ["full"]="$(git log "${default_branch}"..HEAD --format=%B)" \
    ["compare"]="$(git log "${tag_commit}".."${commit}" --format=%B)" \
)
log=${history_type[${branch_history}]}
printf "History:\n---\n%s\n---\n" "$log"

case "$log" in
    *$major_string_token* ) new=$(semver -i major "$tag"); part="major";;
    *$minor_string_token* ) new=$(semver -i minor "$tag"); part="minor";;
    *$patch_string_token* ) new=$(semver -i patch "$tag"); part="patch";;
    *$none_string_token* )
        echo "Default bump was set to none. Skipping..."
        setOutput "old_tag" "$tag"
        setOutput "new_tag" "$tag"
        setOutput "tag" "$tag"
        setOutput "part" "$default_semvar_bump"
        exit 0;;
    * )
        if [ "$default_semvar_bump" == "none" ]
        then
            echo "Default bump was set to none. Skipping..."
            setOutput "old_tag" "$tag"
            setOutput "new_tag" "$tag"
            setOutput "tag" "$tag"
            setOutput "part" "$default_semvar_bump"
            exit 0
        else
            new=$(semver -i "${default_semvar_bump}" "$tag")
            part=$default_semvar_bump
        fi
        ;;
esac

if $pre_release
then
    # get current commit hash for tag
    pre_tag_commit=$(git rev-list -n 1 "$pre_tag" || true)
    # skip if there are no new commits for pre_release
    if [ "$pre_tag_commit" == "$commit" ]
    then
        echo "No new commits since previous pre_tag. Skipping..."
        setOutput "new_tag" "$pre_tag"
        setOutput "tag" "$pre_tag"
        exit 0
    fi
    # already a pre-release available, bump it
    if [[ "$pre_tag" =~ $new ]] && [[ "$pre_tag" =~ $suffix ]]
    then
        if $with_v
        then
            new=v$(semver -i prerelease "${pre_tag}" --preid "${suffix}")
        else
            new=$(semver -i prerelease "${pre_tag}" --preid "${suffix}")
        fi
        echo -e "Bumping ${suffix} pre-tag ${pre_tag}. New pre-tag ${new}"
    else
        if $with_v
        then
            new="v$new-$suffix.0"
        else
            new="$new-$suffix.0"
        fi
        echo -e "Setting ${suffix} pre-tag ${pre_tag} - With pre-tag ${new}"
    fi
    part="pre-$part"
else
    if $with_v
    then
        new="v$new"
    fi
    echo -e "Bumping tag ${tag} - New tag ${new}"
fi

# as defined in readme if CUSTOM_TAG is used any semver calculations are irrelevant.
if [ -n "$custom_tag" ]
then
    new="$custom_tag"
fi

# set outputs
setOutput "new_tag" "$new"
setOutput "part" "$part"
setOutput "tag" "$new" # this needs to go in v2 is breaking change
setOutput "old_tag" "$tag"

# dry run exit without real changes
if $dryrun
then
    exit 0
fi

echo "EVENT: creating local tag $new"
# create local git tag
git tag -f "$new" || exit 1
echo "EVENT: pushing tag $new to origin"

if $git_api_tagging
then
    # use git api to push
    dt=$(date '+%Y-%m-%dT%H:%M:%SZ')
    full_name=$GITHUB_REPOSITORY
    git_refs_url=$(jq .repository.git_refs_url "$GITHUB_EVENT_PATH" | tr -d '"' | sed 's/{\/sha}//g')

    echo "$dt: **pushing tag $new to repo $full_name"

    git_refs_response=$(
    curl -s -X POST "$git_refs_url" \
    -H "Authorization: token $GITHUB_TOKEN" \
    -d @- << EOF
{
    "ref": "refs/tags/$new",
    "sha": "$commit"
}
EOF
)

    git_ref_posted=$( echo "${git_refs_response}" | jq .ref | tr -d '"' )

    echo "::debug::${git_refs_response}"
    if [ "${git_ref_posted}" = "refs/tags/${new}" ]
    then
        exit 0
    else
        echo "::error::Tag was not created properly."
        exit 1
    fi
else
    # use git cli to push
    git push -f origin "$new" || exit 1
fi