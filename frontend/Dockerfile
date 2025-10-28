# Build stage
FROM node:20-alpine as build-stage

# Set working directory
WORKDIR /app

# Install dependencies
COPY CampCameleonXfront/package*.json ./
# COPY vite.config.js ./

# Install dependencies
RUN npm install

# Copy all files
COPY CampCameleonXfront/ ./

# Build the application
RUN npm run build

# production stage
FROM nginx:alpine-slim

# Copy built assets from build stage
COPY --from=build-stage /app/dist /usr/share/nginx/html

# Configuration nginx pour Vue Router
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy the nginx configuration file
EXPOSE 5173

CMD ["nginx", "-g", "daemon off;"]