FROM node:18-alpine

RUN mkdir -p /home/node/app/node_modules && chown -R node:node /home/node/app
USER node
WORKDIR /home/node/app

COPY --chown=node:node package*.json ./

RUN npm install

COPY --chown=node:node . .
RUN ["chmod", "755", "your-script.sh"]
EXPOSE 3000

CMD [ "node", "websocket.js" ]