# YangLocal

Website khám phá địa điểm dành cho sinh viên tại Hà Nội, xây dựng bằng **WordPress, PHP và JavaScript**. Theme trình bày nội dung; plugin riêng quản lý địa điểm, bộ lọc, địa điểm đã lưu, đánh giá và các biểu mẫu.

**Website đang hoạt động:** [nguyenhonggiangcute.helioho.st](https://nguyenhonggiangcute.helioho.st/)

Repo này là bản xuất mã nguồn custom đang chạy, đã tách cấu hình và dữ liệu riêng. Website thật có nội dung và ảnh riêng; bản cài mới chỉ kèm **3 địa điểm hư cấu**, có nhãn rõ ràng, để thử giao diện và chức năng. Không có tài khoản hay mật khẩu mặc định trong repo.

## Chức năng trong mã nguồn

- Tìm kiếm địa điểm, lọc theo loại, khu vực, trường gần đó, ngân sách và tiện ích.
- Trang địa điểm với metadata, nguồn thông tin, trạng thái ảnh và bản đồ khi có tọa độ.
- Danh mục trường, bản đồ campus và khoảng cách khi có dữ liệu phù hợp.
- Lưu địa điểm trên trình duyệt cho khách và theo tài khoản WordPress cho người đăng nhập.
- Đánh giá 1–5 sao dựa trên hệ thống bình luận WordPress.
- Đăng nhập, khôi phục mật khẩu, liên hệ, đề xuất địa điểm và báo thông tin sai.
- Bài viết cẩm nang, mẫu trang sự kiện và công cụ quản trị metadata.

Các chức năng yêu cầu dữ liệu thật như bản đồ địa điểm, sự kiện và ảnh đã xác minh không được giả lập bằng kết quả bịa trong bộ demo.

## Cấu trúc

```text
wp-content/
  plugins/yanglocal-core/
    yanglocal-core.php       # Đăng ký plugin và trang mặc định
    includes/               # Post type, bộ lọc, tài khoản, đánh giá, quản trị
    data/                   # 3 địa điểm hư cấu và danh mục trường từ source
  themes/yanglocal-theme/
    functions.php           # Thiết lập theme, assets, helper và trạng thái ảnh
    front-page.php          # Trang chủ
    single-yl_place.php     # Chi tiết địa điểm
    template-parts/         # Thành phần trình bày
    assets/                 # CSS, JavaScript, SVG nhẹ
```

Địa điểm và sự kiện dùng custom post type; loại địa điểm, khu vực và trường dùng taxonomy. Metadata, đánh giá và nội dung tài khoản nằm trong database WordPress. Trình duyệt gọi `admin-ajax.php` cho các thao tác tương ứng; các handler giữ kiểm tra nonce, quyền và validation vốn có của dự án.

## Cài đặt trên môi trường riêng

1. Cài một bản WordPress mới cùng database riêng. Header của theme/plugin khai báo **WordPress từ 7.1, PHP từ 7.4**; đây là yêu cầu khai báo của source, không phải kết quả kiểm thử mọi phiên bản.
2. Sao chép hai thư mục custom vào `wp-content/plugins/` và `wp-content/themes/` của bản WordPress đó. Không cần npm hay bước build.
3. Đăng nhập bằng tài khoản quản trị bạn tạo trong quá trình cài WordPress. Kích hoạt **YangLocal Core** trước, rồi theme **YangLocal**.
4. Plugin tạo các trang chức năng và thiết lập trang chủ/trang bài viết/trang riêng tư. Kiểm tra **Cài đặt → Đọc** và lưu lại **Cài đặt → Đường dẫn tĩnh**.
5. Nếu muốn có nội dung để thử, vào **Công cụ → YangLocal Setup → Nhập dữ liệu hư cấu**. Đồng bộ chỉ áp dụng cho các slug demo; bản xuất công khai đã vô hiệu chức năng dọn nội dung cũ.
6. Thêm địa điểm của bạn từ trang quản trị, nhập nguồn và tọa độ khi có bằng chứng; tải ảnh mà bạn có quyền sử dụng vào Media Library.

Hãy dùng một website thử nghiệm riêng. Bản xuất này không phải gói sao lưu database hoặc bản nâng cấp trực tiếp cho website thật.

## Phụ thuộc và cấu hình tùy chọn

Google Fonts tải phông qua mạng; Leaflet 1.9.4 tải từ CDN với cơ chế dự phòng; bản đồ dùng OpenStreetMap và giữ attribution. Khi mạng chặn CDN, phông hệ thống và trạng thái thông báo của bản đồ có thể xuất hiện.

Google Places và công cụ tải ảnh là tùy chọn, cần key được cấu hình trong WordPress, HTTPS outbound và thư mục uploads ghi được. Không đưa key vào mã nguồn. Chỉ tải ảnh sau khi đã kiểm tra quyền sử dụng và ghi nguồn. Biểu mẫu liên hệ lưu tin nhắn trong WordPress; email khôi phục mật khẩu còn phụ thuộc cấu hình gửi mail của host.

## Bản công khai khác website thật ở đâu

- Không gồm WordPress core, `wp-config.php`, database, tài khoản, đánh giá, tin nhắn, uploads hoặc file sao lưu.
- Bộ dữ liệu địa điểm thật không được xuất bản lại; bộ demo có 3 địa điểm hư cấu, không số điện thoại, tọa độ thật hay đánh giá giả.
- Ảnh bitmap chưa có hồ sơ quyền đầy đủ được bỏ khỏi repo. Header/footer dùng SVG mark có sẵn, trang đăng nhập dùng SVG dự phòng.
- Công cụ dọn nội dung seed cũ bị vô hiệu trong bản công khai; thao tác nhập demo không xóa hoặc đưa bài cũ về bản nháp.
- Danh mục trường giữ thông tin và nguồn theo snapshot cũ trong source; cần xác minh lại trước khi dùng làm dữ liệu vận hành.

Website đang chạy không bị thay đổi bởi quá trình xuất bản repo này.

## Kiểm tra và giới hạn

Bản xuất được kiểm tra cú pháp PHP/JavaScript, JSON, tham chiếu ảnh bắt buộc và nội dung nhạy cảm trước khi công khai. Website thật đã được xem trực tiếp. Chưa chạy kiểm thử tích hợp đầy đủ trên một database WordPress mới; không coi việc kiểm tra cú pháp là chứng minh mọi luồng backend đều đã được thử.

Giá, giờ mở cửa, nguồn ảnh và vị trí có thể thay đổi. Dữ liệu hư cấu chỉ dùng trình diễn. Website không cam kết thời gian hoạt động hoặc host miễn phí vĩnh viễn.

## Tự trình diễn khi phỏng vấn

Tạo một địa điểm trên bản thử nghiệm, lọc theo ngân sách, mở trang chi tiết, lưu khi đăng nhập rồi gửi đánh giá. Giải thích cách tách plugin khỏi theme, dữ liệu trong WordPress và cách nonce/quyền được kiểm tra ở handler. Chỉ mô tả chức năng và phần bạn hiểu, không suy ra số người dùng hay thành tích từ repo.

## Quyền sử dụng

Xem [SOURCE_NOTICE.md](SOURCE_NOTICE.md). Bản gốc không kèm giấy phép phân phối chung; repo không tự gán giấy phép MIT cho toàn bộ mã nguồn, nội dung hoặc ảnh.
