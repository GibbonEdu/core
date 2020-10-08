#!/bin/bash

BASENAME=$(basename $0)
BASEPATH=$(dirname $(realpath $0))

# Print usage message
function usage {
    cat <<EOF
usage: $BASENAME -k <GITHUB_API_TOKEN> -r <GITHUB_REPO_SLUG> -t <TAG> <DIST_PATH>

Argument:
    DIST_PATH  The folder containing all the files that needed to be included in
               the release. Or the path to one single file to be released.

Options
    -k    OAuth2 Token for releasing. See "Personal Access Tokens" in GitHub Docs.
    -r    GitHub repository slug string (i.e. "username/reponame").
    -t    Tag for the release. Usually in "vX.X.X" format.
EOF
}

#
# readopts
#
# Read options into global variables:
#   GITHUB_API_TOKEN   OAuth2 Token for releasing. See "Personal Access Tokens" in GitHub Docs.
#   GITHUB_REPO_SLUG   GitHub repository slug string (i.e. "username/reponame").
#   TAG                The tag name of the release to create.
#   DISTPATH           The path where the assets locate.
#
function readopts {
    # Read all options as variables
    while getopts ":k:r:t:" ARG; do
        case ${ARG} in
            k)
                GITHUB_API_TOKEN="$OPTARG"
                ;;
            r)
                GITHUB_REPO_SLUG="$OPTARG"
                ;;
            t)
                TAG="$OPTARG"
                ;;
            \?)
                echo "Invalid Option: -$OPTARG" 1>&2
                echo
                usage
                exit 1
                ;;
        esac
    done

    # Remove all options from $@
    shift $((OPTIND -1))
    DISTPATH=$1

    if [ -z "$GITHUB_API_TOKEN" ]; then
        echo "flag -k is not set"
        echo
        usage
        exit 1
    fi
    if [ -z "$GITHUB_REPO_SLUG" ]; then
        echo "flag -r is not set"
        echo
        usage
        exit 1
    fi
    if [ -z "$TAG" ]; then
        echo "flag -t is not set"
        echo
        usage
        exit 1
    fi
}

#
# release_create [RELEASE_ID_VAR]
#
# Create a release from given information. Requires environment variables:
#   GITHUB_API_TOKEN   OAuth2 Token for releasing. See "Personal Access Tokens" in GitHub Docs.
#   GITHUB_REPO_SLUG   GitHub repository slug string (i.e. "username/reponame").
#   TAG                The tag name of the release to create.
#
# Argument:
#   RELEASE_ID_VAR     A variable reference to receive the retrieved release id, if any.
#
function release_create {
    # Expects a variable name in first argument for returning release id
    declare -n id=$1
    local RESPONSE=$(curl \
        -X POST \
        -H "Authorization: token $GITHUB_API_TOKEN" \
        -H "Accept: application/vnd.github.v3+json" \
        -d "{\"tag_name\": \"$TAG\"}" \
        --silent \
        "https://api.github.com/repos/$GITHUB_REPO_SLUG/releases")
    eval $(echo "$RESPONSE" | grep -m 1 "id.:" | grep -w id | tr : = | tr -cd '[[:alnum:]]=')
    if [ -z "$id" ]; then
        echo "Failed to create release for tag: $TAG"
        echo "$RESPONSE"
        return 1
    fi
}

#
# release_get_id [RELEASE_ID_VAR]
#
# Find the release id from given information. Requires environment variable:
#   GITHUB_API_TOKEN   OAuth2 Token for releasing. See "Personal Access Tokens" in GitHub Docs.
#   GITHUB_REPO_SLUG   GitHub repository slug string (i.e. "username/reponame").
#   TAG                The tag name of the release to retrieve.
#
# Argument:
#   RELEASE_ID_VAR     A variable reference to receive the retrieved release id, if any.
#
function release_get_id {
    declare -n id=$1
    local RESPONSE=$(curl -sH "$AUTH" "https://api.github.com/repos/$GITHUB_REPO_SLUG/releases/tags/$TAG")
    eval $(echo "$RESPONSE" | grep -m 1 "id.:" | grep -w id | tr : = | tr -cd '[[:alnum:]]=')
}

#
# release_add_asset
#
# Find the release id from given information. Requires environment variable:
#   GITHUB_API_TOKEN   OAuth2 Token for releasing. See "Personal Access Tokens" in GitHub Docs.
#   GITHUB_REPO_SLUG   GitHub repository slug string (i.e. "username/reponame").
#   TAG                The tag name of the release to retrieve.
#   FILE               The full path to the file to add.
#
# Argument:
#   RELEASE_ID_VAR     A variable reference to receive the retrieved release id, if any.
#
function release_add_asset {
    # Upload asset
    if [ -s "$FILE" ]; then
        local FILENAME=$(basename $FILE)
        local LABEL="Installation Bundle (${FILENAME#*.})"
        echo "Uploading asset: $FILENAME"
        local QUERY="name=$(urlencode $FILENAME)&label=$(urlencode "$LABEL")"
        echo "https://uploads.github.com/repos/$GITHUB_REPO_SLUG/releases/$RELEASE_ID/assets?$QUERY"
        curl \
            -X POST \
            -H "Authorization: token $GITHUB_API_TOKEN" \
            -H "Accept: application/vnd.github.v3+json" \
            -H "Content-Type: application/x-gtar" \
            --data-binary @"$FILE" \
            "https://uploads.github.com/repos/$GITHUB_REPO_SLUG/releases/$RELEASE_ID/assets?$QUERY"
    else
        echo "Skipping empty asset: $FILE"
    fi
}

#
# urlencode
#
# Encode string into URL-safe string.
#
function urlencode {
    # urlencode <string>

    old_lc_collate=$LC_COLLATE
    LC_COLLATE=C

    local length="${#1}"
    for (( i = 0; i < length; i++ )); do
        local c="${1:$i:1}"
        case $c in
            [a-zA-Z0-9.~_-]) printf '%s' "$c" ;;
            *) printf '%%%02X' "'$c" ;;
        esac
    done

    LC_COLLATE=$old_lc_collate
}


#
# main
#

# Read arguments and options into global variables
readopts $@

## FIXME: Validate token before use

# Create or find the release for the tag
if GITHUB_API_TOKEN="$GITHUB_API_TOKEN" GITHUB_REPO_SLUG="$GITHUB_REPO_SLUG" TAG="$TAG" release_create RELEASE_ID > /dev/null; then
    echo "successfully created release id: $RELEASE_ID, tag: $TAG"
else
    GITHUB_API_TOKEN="$GITHUB_API_TOKEN" GITHUB_REPO_SLUG="$GITHUB_REPO_SLUG" TAG="$TAG" release_get_id RELEASE_ID
    if [ -z "$RELEASE_ID" ]; then
        echo "unable to find release for tag: $TAG"
        exit 1
    fi
    echo "retrieved existing release id: $RELEASE_ID, tag: $TAG"
fi
echo

# Upload assets in the dist folder
for FILE in $DISTPATH/*; do
    if [ -f "$FILE" ]; then
        GITHUB_API_TOKEN="$GITHUB_API_TOKEN" GITHUB_REPO_SLUG="$GITHUB_REPO_SLUG" RELEASE_ID="$RELEASE_ID" FILE="$FILE" release_add_asset
    else
        echo "not a file. skipped: $FILE"
    fi
done
