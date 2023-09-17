FROM node:18-alpine

RUN apk --no-cache add bash git curl jq && npm install -g semver

COPY entrypoint.sh /entrypoint.sh
RUN ["chmod", "+x", "/entrypoint.sh"]
ENTRYPOINT ["/entrypoint.sh"]