FROM node:20-alpine

WORKDIR /app

# 使用淘宝 npm 镜像
RUN npm config set registry https://registry.npmmirror.com

COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host"]
