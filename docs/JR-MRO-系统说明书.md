# JR MRO 商城 · 系统说明书（最新整合版）

> 版本：2026-10-18　|　适用系统：Windows + WSL2 + Bagisto 2.4　|　域名：https://www.jrmro.com
> 本说明书整合部署、网络、账号、前台、后台、导入、单据中心、运维、故障排查全部内容。

---

## 一、系统概述

**JR MRO 商城**是面向越南市场的工业品（MRO，非生产性物料）B2B 电商平台，基于开源 **Bagisto 2.4**（Laravel + PHP 8.3 + MariaDB）二次开发，覆盖五金工具、电动工具、紧固件、劳保用品、焊接材料、办公用品等大类。

**核心能力**：商品多语言浏览、购物车下单、询价（RFQ）、购物车生成标准报价单、订单 + 电子签章、单据全生命周期管理、电子表格批量导入、多货币（VND/CNY/USD）与税率/汇率配置。

---

## 二、部署架构与环境

### 2.1 运行环境
| 项 | 值 |
|---|---|
| 物理机 | Windows（本机），电源设为**永不休眠、15 分钟关屏** |
| 子系统 | **WSL2** Ubuntu 22.04（内核 5.10），承载全部服务 |
| 电商 | Bagisto 2.4（Laravel），代码目录 `/home/galaxy/bagisto` |
| Web | Nginx（80/443 强制 HTTPS；8080 供隧道） |
| PHP | 8.3-FPM（4 worker） |
| 数据库 | MariaDB（127.0.0.1:3306，库 `bagisto`） |
| 缓存/队列 | file cache + database 队列 + 常驻 worker |
| 报价系统 | 独立 uvicorn 服务（端口 8001，`/home/galaxy/quote`） |
| 内网 DNS | dnsmasq |
| 外网 | cloudflared 隧道（临时地址） |

### 2.2 目录与关键文件
| 路径 | 说明 |
|---|---|
| `/home/galaxy/bagisto` | 商城主程序 |
| `/home/galaxy/quote` | 报价系统（uvicorn :8001） |
| `/home/galaxy/logs/startup.log` | 启动日志 |
| `/tmp/cf_tunnel.log` | 隧道日志（含外网地址） |
| `/home/galaxy/backups` | 每日数据库备份 |
| `/etc/nginx/conf.d/tunnel_8080.conf` | 隧道 nginx 入口 |

### 2.3 运行服务清单
| 服务 | 状态/职责 |
|---|---|
| nginx | ✅ Web 服务（强制 HTTPS） |
| php8.3-fpm | ✅ PHP 解析 |
| mariadb | ✅ 数据库 |
| cron | ✅ 定时调度（每分钟 schedule:run） |
| queue worker | ✅ 异步任务/邮件 |
| uvicorn(:8001) | ✅ 报价系统 |
| cloudflared | ✅ 外网隧道 |

---

## 三、网络访问

### 3.1 访问地址
| 用途 | 地址 |
|---|---|
| 前台商城 | `https://www.jrmro.com` |
| 管理后台 | `https://www.jrmro.com/admin` |
| 外网（临时隧道） | `https://<隧道地址>.trycloudflare.com`（每次重启会变） |
| 局域网 | 本机 IP + hosts 解析 `www.jrmro.com` |

### 3.2 网络拓扑
```
局域网设备 ─► 局域网内网IP (Windows, WLAN)
                 │  netsh portproxy（自动更新）
                 ▼
           WSL2 (172.18.x.x, IP 随启动变化)
           nginx:80/443 ─► php-fpm（unix socket，稳定）
           8080 ─► cloudflared 隧道 ─► 外网 trycloudflare 域名
```

### 3.3 外网隧道
- 免费隧道地址**每次重启电脑会变**；双击 `get_tunnel_url.cmd` 查询当前最新地址。
- 正式公网上线：注册正式域名 `jrmro.com` → 配置 Cloudflare 命名隧道 → 地址永久固定。

### 3.4 局域网其他设备访问
需在访问设备 hosts 添加：`<本机IP> www.jrmro.com jrmro.com`，或由内网 DNS（dnsmasq）解析。

---

## 四、账号与权限

| 系统 | 账号 | 密码 |
|---|---|---|
| 商城后台 | `admin@example.com` | `<your_admin_password>` |
| 数据库 | `bagisto` | `<your_db_password>`（库 `bagisto`） |
| WSL 用户 | `galaxy` | （本人自设） |
| Windows 用户 | `ktb` | （本人自设） |
| Cloudflare | `galaxyteck6886@gmail.com` | （本人自设） |

> 后台与数据库均为强密码（≥12 位，大小写+数字+符号），`APP_ENV=production`、`APP_DEBUG=false`。

---

## 五、前台功能

### 5.1 主题与多语言
- **工业科技风**（Home Depot 橙 `#F96302` + 白底 + 深灰页脚），简洁大方。
- **4 种语言**：简体中文（默认）/ 繁体中文 / 越南语 / 英文，右上角切换或 URL `?locale=vi`。
- 商品名、分类、属性、界面文案全部 4 语言。
- 品牌统一：JR MRO 超市（Siêu thị JR MRO / JR MRO Supermarket）。

### 5.2 商品浏览
- 首页：头图 + 新品上架 + 热门分类 + 商品网格（卡片带图、VND 价、新品标识）。
- 分类导航：横楣 13 个顶级大类 + 分类图标 + 抽屉菜单。
- 商品详情：多图、规格、价格（按汇率换算 VND）、库存、加入购物车 / 立即购买 / **我要询价**。
- 条件筛选：分类、价格、品牌等筛选 + 排序（数据均带真实商品图）。

### 5.3 购物车 / 下单
- 购物车：增减数量、合计（VND）、移除。
- 下单：收货地址（含**越南行政区划**）、配送方式、支付方式（COD 货到付款 / 银行转账 Vietcombank）。
- 订单：订单确认、状态跟踪、发票邮件。
- 邮件：注册确认、下单、发票（SMTP）。

### 5.4 询价（RFQ）
- 商品详情页「我要询价」→ 表单（姓名/邮箱/数量/电话/公司/留言）。
- 提交后：存库 `mro_rfqs`，邮件通知管理员。
- 后台报价后邮件通知客户。

### 5.5 购物车生成报价单
- 登录客户购物车页显示「生成 MRO 标准报价单」，填姓名/邮箱/电话/公司 → 一键生成草稿报价单（读取购物车全部商品）。
- 采用全局默认税率/汇率，生成后到后台「单据中心 → 报价单」调价、改税/汇率、打印、导出、邮件。

### 5.6 货币 / 税 / 汇率
- 支持货币：**VND（越南盾，基准）/ CNY / USD**。
- 商品价按 **CNY 基准价**存储，前台按汇率换算显示 VND。
- 税率（VAT）默认 10%，汇率默认 3500（可在后台报价单全局配置调整，如 3900）。

---

## 六、后台功能

### 6.1 商品管理（原生 + 增强）
商品列表入口：**Catalog → Products**。除原生新增/编辑/删除/复制外，已交付**增强功能**：

| # | 功能 | 入口 |
|---|---|---|
| 27 | 批量改价/库存/移分类 | 商品列表顶部「批量操作」 |
| 28 | 行内快速编辑 | 每行「快速编辑」按钮 |
| 29 | 批量上传图片 | 顶部「批量上传图片」（ZIP 或多文件，按 SKU 匹配） |
| 30/37 | 图片集中管理（单删/批量删） | 顶部「图片库」 |
| 31 | 表格导入支持图片 URL | 导入 CSV/xlsx 的 `images` 列 |
| 32/38 | 复制商品（含图/不含图） | 批量操作 → 批量复制 |
| 33/36 | 定时自动批量导入（可配置） | 命令行 + 计划任务 |
| 39 | 导入历史可视化 | 顶部「导入记录」 |
| 40 | 手动维护商品（精简表单） | 顶部「＋ 手动维护商品」 |

### 6.2 电子表格导入
- 支持 **CSV / XLSX**，含**下拉选项**（material_id / unit_id / category_l1_id 等防填错）、必填校验。
- `images` 列支持**本地文件名**或**图片 URL**（分号分隔多个，URL 自动下载）。
- 导入结果写入 `mro_import_logs`，后台「导入记录」可查（成功/跳过/失败/耗时）。
- 手动：`php artisan mro:import-products <csv> [--images=dir] [--clear]`；定时：默认每日 08:00（可配置）。

### 6.3 单据中心（统一交易单据入口）
顶级菜单 **单据中心**（`/admin/dealings`），含子菜单：
- **报价单**：购物车生成的报价单，可调价/税率/汇率、导出 Excel/PDF、邮件发客户、打印多语种标准报价单、状态流转、有效期。
- **询价单**：前台 RFQ 询价管理（列表 + 详情报价 + 邮件通知）。
- **订单**：购物车订单（原生销售订单入口）。

概览页：三张状态卡片（询价/报价/订单）+ 最近单据列表 + 快捷入口 + 单据流转说明。

### 6.4 单据状态机
```
购物车 / 询价单（外部数据源，只读）
   ▼ 读取
报价单 draft → issued(带有效期) → confirmed / expired / cancelled
   ▼ 客户在线确认
订单 pending_sign → signed → completed / cancelled
   ▼ 卖方+客户签章（记录式，哈希锁定）
已生效订单（不可篡改，全链路可追溯）
```
- 报价单号 `Q<id>`、订单号 `SO<Ymd><序列>`，全局唯一。
- `content_hash = sha256(...)` 防篡改，篡改实时告警。
- 全部关键操作写审计日志（操作人/IP/变更前后值）。

### 6.5 系统配置
- 报价单全局默认汇率 / 税率（`catalog.quote.default_exchange_rate / default_tax_rate`）。
- 邮件、配送、支付、货币、时区等 Bagisto 原生配置。

---

## 七、数据与备份

- **商品**：约 500 个，全库 500/500 配齐真实网络商品图（webp），无缺图。
- **分类**：74 个，13 个顶级大类均带图标。
- **备份**：每日 03:00 `mysqldump` 到 `/home/galaxy/backups`，保留 7 份。
- **数据库**：约 153 表。

---

## 八、运维手册

### 8.1 启动
- **一键启动**：双击 `启动MRO商城.cmd`（自动启动全部服务并显示地址）。
- **开机自启**：WSL 启动自动执行 `/usr/local/bin/start-bagisto` → `/home/galaxy/start-services.sh`；Windows 登录自动触发。

### 8.2 常用命令（PowerShell）
```powershell
# 查看服务状态
wsl -u root service --status-all
# 启动全部服务
wsl -u root bash /home/galaxy/start-services.sh
# 清缓存（改数据后前台不刷新时用）
wsl -u root bash -lc 'cd /home/galaxy/bagisto && su galaxy -c "php artisan cache:clear"'
# 手动导入商品
wsl -u root bash -lc 'cd /home/galaxy/bagisto && su galaxy -c "php artisan mro:import-products /路径.csv --images=/路径/images --clear"'
# 查看外网隧道地址
wsl -u root grep -o "https://[a-z0-9-]*\.trycloudflare\.com" /tmp/cf_tunnel.log | tail -1
# 重启隧道（地址失效时）
wsl -u root pkill -f cloudflared
wsl -u root cloudflared tunnel --url http://127.0.0.1:8080 --logfile /tmp/cf_tunnel.log --loglevel info &
```

### 8.3 日志
- 启动：`/home/galaxy/logs/startup.log`
- 隧道：`/tmp/cf_tunnel.log`
- Laravel：`/home/galaxy/bagisto/storage/logs/laravel.log`

---

## 九、安全与生产就绪状态

| 维度 | 状态 |
|---|---|
| 生产模式 | ✅ APP_ENV=production、DEBUG=false |
| HTTPS | ✅ 全站强制 HTTPS（自签证书，正式公网建议换 Let's Encrypt） |
| 强密码 | ✅ 后台/数据库均强密码 |
| 队列 | ✅ database + 常驻 worker |
| 定时调度 | ✅ crontab 每分钟 |
| 数据备份 | ✅ 每日 mysqldump 保留 7 份 |
| 时区 | ✅ Asia/Ho_Chi_Minh |
| 性能 | ✅ WSL2 根治 30 秒卡死，首页局域网约 0.08s |
| 在线支付 | 🟡 仅 COD/银行转账，越南在线网关待接入 |
| 缓存 | 🟡 file 存储，正式公网建议改 Nginx fastcgi_cache |

---

## 十、常见故障排查（FAQ）

| 现象 | 原因 | 处理 |
|---|---|---|
| 前台商品改了没变化 | 页面/列表缓存 | `php artisan cache:clear` 后强制刷新（Ctrl+F5） |
| 商品图片不显示 | 旧缓存坏图 / Mixed Content | 强制刷新；已修 ImageUrlBuilder 跟随协议 |
| 外网隧道打不开 | 临时域名失效 | 重启隧道生成新地址（见 8.2） |
| 30 秒卡死（历史） | 已由 WSL2 根治 | 无复发；若再出现查 laravel.log |
| 邮件收不到 | SMTP 被拒（550/552） | 换有信誉的 SMTP（企业/云邮箱） |
| 后台 POST 419 | CSRF token 过期 | 重新打开后台 dashboard 获取新 token |
| 重启后没启动 | WSL 未被调用 | 双击 `启动MRO商城.cmd`，或查自启脚本 |

---
*本说明书随系统迭代持续更新。敏感凭据见《JR-MRO-系统部署与凭据手册》。*
