# Nguồn và phạm vi bản xuất

Chủ dự án: Nguyen Hong Giang. Mã custom được lấy từ theme `yanglocal-theme` và plugin `yanglocal-core` của website YangLocal ngày 08/10/2026. Ngày 09/10/2026 bổ sung gói WordPress core 7.1.3 chính thức, tách khỏi phần custom trong thông tin nguồn.

Bản gốc không có LICENSE, COPYING hoặc NOTICE và không khai báo giấy phép trong header theme/plugin. Việc công khai repo không tự tạo quyền tái sử dụng chung cho toàn bộ source hoặc dữ liệu. Liên hệ chủ dự án nếu cần quyền sử dụng; không mặc định coi source là MIT. WordPress và thư viện bên ngoài có giấy phép riêng.

Các ảnh bitmap, ảnh địa điểm và screenshot đóng gói trước đây được bỏ khỏi bản xuất vì không có manifest quyền đầy đủ đi kèm. Các SVG dự phòng và mark có sẵn trong source chỉ chứa hình vector cục bộ. Không có ảnh người dùng hoặc database đi kèm. Dữ liệu địa điểm minh họa mới là hư cấu; danh mục trường còn giữ các URL nguồn lịch sử trong từng record và chưa được xác minh lại trong lần xuất bản này.

Các phụ thuộc tải riêng qua mạng gồm Google Fonts, Leaflet và OpenStreetMap tiles. Giữ attribution bản đồ; kiểm tra điều khoản/giấy phép của từng nhà cung cấp khi vận hành hoặc phân phối lại. Tên thương hiệu, dữ liệu và ảnh từ bên thứ ba không được cấp quyền chỉ bởi repo này.

## WordPress core và phụ thuộc mặc định

Core được lấy nguyên byte từ [WordPress.org](https://wordpress.org/wordpress-7.1.3.zip), đối chiếu [SHA-1 công bố](https://wordpress.org/wordpress-7.1.3.zip.sha1) và ghi SHA-256 trong WORDPRESS_SOURCE.json. WordPress dùng GPLv2 hoặc mới hơn; bản giấy phép đầy đủ nằm ở [license.txt](license.txt). Giữ các readme, thông báo giấy phép thư viện, plugin/theme mặc định và nguồn ảnh của gói upstream. Các giấy phép đó không tự cấp phép lại dữ liệu riêng, thương hiệu hay toàn bộ mã custom thiếu tuyên bố giấy phép ở trên.

Gói chuẩn này không chứa tài khoản, khóa Akismet, database hoặc ảnh uploads từ host. Ba mẫu phần mềm khớp host chỉ xác nhận phiên bản và header đã đọc; không chứng minh toàn bộ core trên host giống gói chính thức.
