# 项目概览

这是一个名为“zChain GuestBook v4.00”的基于 PHP 的多用户留言簿应用程序。它使用自定义的基于文件的数据库系统来存储留言簿条目和配置。项目结构清晰，主 `src` 目录包含核心逻辑、配置 (`etc`)、二进制/类文件 (`bin`)、图像资源 (`img`) 和 HTML 模板 (`template`)。它利用了一个名为 `xingTemplate` 的自定义模板引擎。该项目是根据 GNU GPL 发布的开源项目。

# 构建与运行

*   **依赖项：** 项目使用 Composer 进行依赖管理，`phpunit/phpunit` 作为开发依赖项。
*   **运行应用程序：** 这是一个传统的 PHP Web 应用程序。它需要由安装了 PHP 的 Web 服务器（例如 Apache、Nginx）提供服务。主入口点是 `src/index.php`。应用程序使用基于 `$_GET['op']` 的简单路由机制来包含不同的功能脚本。
*   **测试：** 测试位于 `tests` 目录中，可以使用 PHPUnit 运行。
    *   命令：`vendor/bin/phpunit`

# 开发约定

*   **语言：** PHP。
*   **数据存储：** 自定义的基于文件的数据库系统。数据文件存储在 `src/setup.php` 中 `$filepath` 指定的目录中（例如，`./ZDB_c3416034cf9f7cc8`）。
*   **配置：** `src/setup.php` 中集中配置，包含管理员凭据和其他设置的硬编码值。
*   **模板：** 使用自定义模板引擎 `xingTemplate`。模板是位于 `src/template` 中的 HTML 文件，并编译到 `../../tmp/template_compile`。
*   **安全性：** 该应用程序存在一些典型的旧 PHP 应用程序的安全问题，包括：
    *   硬编码的管理员凭据。
    *   使用 MD5 进行密码哈希。
    *   直接使用超全局变量（`$_GET`、`$_REQUEST`、`$_COOKIE`、`$_SESSION`、`GLOBALS`），没有一致且健壮的输入清理/验证。
    *   使用已弃用的 `eregi` 函数。
    *   允许在模板中直接插入 PHP 代码（如果 `PHP_off` 为 `false`）。
*   **代码风格：** 过程式编程和面向对象编程的混合。HTML 通常使用 heredoc 语法直接在 PHP 文件中生成。
*   **文件结构：**
    *   `src/`：主源代码。
        *   `bin/`：包含核心 PHP 类和脚本（例如，`class_book_list.php`、`controller_show_book.php`）。
        *   `etc/`：包含由 `index.php` 包含的与配置相关的 PHP 脚本（例如，`list.php`、`admin.php`）。
        *   `img/`：图像资源。
        *   `lib/`：外部库，特别是 `xingTemplate`。
        *   `style/`：CSS 文件。
        *   `template/`：HTML 模板文件。
    *   `tests/`：PHPUnit 测试文件。
    *   `vendor/`：Composer 依赖项。
