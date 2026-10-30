# YangLocal

Website khám phá địa điểm tại Hà Nội dành cho sinh viên, dùng WordPress,
một theme riêng và plugin quản lý địa điểm.

[Demo](https://nguyenhonggiangcute.helioho.st/)

## Chức năng

- Tìm địa điểm theo loại, khu vực, trường gần đó, ngân sách và tiện ích.
- Xem thông tin, ảnh, nguồn và bản đồ của địa điểm.
- Lưu địa điểm yêu thích trên trình duyệt hoặc theo tài khoản.
- Đánh giá từ 1 đến 5 sao.
- Gửi liên hệ, đề xuất địa điểm và báo thông tin sai.
- Quản lý địa điểm, trường học, sự kiện và bài cẩm nang trong WordPress.

## Cài đặt

Repository có sẵn WordPress core, theme và plugin.

1. Đặt mã nguồn vào document root của một website thử nghiệm.
2. Tạo database MySQL/MariaDB và mở website để chạy trình cài WordPress.
3. Tạo tài khoản quản trị, sau đó kích hoạt **YangLocal Core** và theme **YangLocal**.
4. Kiểm tra **Cài đặt → Đọc**, rồi lưu lại **Cài đặt → Đường dẫn tĩnh**.
5. Nếu cần dữ liệu thử, mở **Công cụ → YangLocal Setup → Nhập dữ liệu hư cấu**.

Bộ demo có ba địa điểm hư cấu. Tài khoản, database, ảnh tải lên và cấu hình của
website đang chạy không nằm trong repository. Tệp `wp-config.php` được tạo khi cài đặt.

Nếu bố trí WordPress trong thư mục `/wp` và dùng domain root cho trang chính,
xem [hướng dẫn bố trí trên HelioHost](deployment/helio-domain-root/README.md).

## Phần mã riêng

- `wp-content/plugins/yanglocal-core/`: post type, taxonomy, bộ lọc, tài khoản,
  đánh giá và các biểu mẫu.
- `wp-content/themes/yanglocal-theme/`: giao diện, mẫu trang, CSS và JavaScript.
- `wp-content/plugins/yanglocal-core/data/`: dữ liệu demo và danh mục trường.
- `deployment/`: mẫu cấu hình cho cách bố trí trên host.

Địa điểm và sự kiện dùng custom post type. Bộ lọc dùng taxonomy và metadata.
Các thao tác từ trình duyệt gọi `admin-ajax.php`; handler kiểm tra nonce,
quyền truy cập và dữ liệu đầu vào.

## Tích hợp

Bản đồ dùng Leaflet và OpenStreetMap. Google Fonts có phông hệ thống dự phòng.
Google Places và công cụ tải ảnh chỉ hoạt động khi được cấu hình khóa riêng.
Email khôi phục mật khẩu phụ thuộc cấu hình gửi mail của host.

Giá, giờ mở cửa và địa chỉ cần được kiểm tra lại khi thêm địa điểm thực tế.
Chỉ tải ảnh có quyền sử dụng và giữ thông tin nguồn.

## Nguồn và giấy phép

WordPress và các thư viện đi kèm giữ giấy phép riêng.
Phần theme/plugin chưa có giấy phép tái sử dụng chung.
Chi tiết ở [thông tin nguồn](docs/source-notice.md);
thông tin gói core ở [wordpress-source.json](docs/wordpress-source.json).
