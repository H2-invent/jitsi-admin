FROM node:18-alpine

RUN mkdir -p /home/node/app/node_modules && chown -R node:node /home/node/app
USER root
WORKDIR /home/node/app

COPY --chown=node:node package*.json ./

RUN npm install

USER node
COPY --chown=node:node . .
RUN ["chmod", "755", "websocket.js"]
EXPOSE 3000

CMD [ "node", "websocket.js" ]