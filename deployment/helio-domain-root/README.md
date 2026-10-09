# Bố trí WordPress giống HelioHost hiện tại

Repo có thể chạy trực tiếp tại document root trên một database mới. Để bố trí core tại `/wp` và nội dung công khai ở domain root như host hiện tại:

1. Tạo website/database thử nghiệm riêng; chép toàn bộ nội dung repo vào thư mục `/wp` của website đó.
2. Chạy trình cài tại `/wp/wp-admin/install.php`, tự đặt tài khoản quản trị. Kích hoạt YangLocal Core rồi theme YangLocal.
3. Trong Cài đặt → Tổng quan, đặt Địa chỉ WordPress thành URL thử nghiệm cộng `/wp`, còn Địa chỉ trang web là URL thử nghiệm ở root. Dùng URL của bạn, không dùng domain của chủ dự án.
4. Chép `index.php` trong thư mục hướng dẫn này ra document root. Với Apache/mod_rewrite, chép `.htaccess.example` thành `.htaccess` ở root rồi lưu lại Đường dẫn tĩnh. Nếu máy chủ có quy tắc riêng, giữ cấu hình phù hợp máy chủ đó.
5. Đăng nhập quản trị tại `/wp/wp-admin/`. Gói demo hư cấu được nhập thủ công từ Công cụ → YangLocal Setup.

Hai tệp front controller/rewrite lấy từ source host và không chứa cấu hình đăng nhập. Hướng dẫn không tải database, uploads, khóa hay tài khoản thật. Không dùng thao tác này để ghi đè/reset website đang có dữ liệu; chưa xác minh cài đặt tích hợp trên database mới trong lần xuất bản này.
