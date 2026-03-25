# Docker 部署（不影响现有 WordPress）

本目录用于把 **微擎（WeEngine）+ 人人商城（ewei_shopv2）** 以 Docker 方式运行在服务器上，默认只监听本机端口，避免占用/干扰服务器上已有的 WordPress（80/443、PHP-FPM、MySQL 等）。

## 目录说明

- `docker-compose.yml`：nginx + php-fpm + mysql + redis
- `nginx/conf.d/ewei_shop.conf`：站点配置（默认 `/` 走 `app/`，后台走 `/web/`）
- `.env.example`：环境变量样例

## 本地启动（或服务器启动）

1) 复制环境变量文件

```bash
cd deploy/docker
cp .env.example .env
```

2) 启动

```bash
docker compose up -d --build
```

3) 访问

- **前台**：`http://127.0.0.1:18080/`
- **后台**：`http://127.0.0.1:18080/web/`
- **安装**（首次）：`http://127.0.0.1:18080/install.php`

## 生产建议（绑定域名 rrsc.667788.cool）

为了不影响 WordPress，建议由服务器现有的 Nginx/Apache 占用 80/443，并做反向代理到：

- `http://127.0.0.1:18080`

反代规则大致是把 `rrsc.667788.cool` 的流量转发到该端口即可（HTTPS 证书仍由宿主机 Web 服务管理）。

## 数据持久化

- MySQL：`mysql_data` volume
- Redis：`redis_data` volume

