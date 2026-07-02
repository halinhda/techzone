# TechZone 🛒

TechZone là dự án website thương mại điện tử. Tài liệu này hướng dẫn cách cài đặt và sử dụng dự án cục bộ.

## 🚀 Cài đặt & Khởi động
1. **Môi trường**: Mở XAMPP và khởi chạy **Apache** + **MySQL**.
2. **Cài đặt mã nguồn**: Đảm bảo thư mục dự án nằm ở `C:\xampp\htdocs\bainhom`.
3. **Cơ sở dữ liệu**:
   - Truy cập: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
   - Tạo database mới tên là `techzone_db`.
   - Import file `database.sql` có trong thư mục dự án.

## 🔗 Các liên kết chính
- **Trang chủ**: [http://localhost/bainhom/index.php](http://localhost/bainhom/index.php)
- **Giỏ hàng**: [http://localhost/bainhom/views/cart.php](http://localhost/bainhom/views/cart.php)
- **Thanh toán**: [http://localhost/bainhom/checkout.php](http://localhost/bainhom/checkout.php)
- **Hỗ trợ**: [http://localhost/bainhom/views/support.php](http://localhost/bainhom/views/support.php)
- **Quản trị (Admin)**: [http://localhost/bainhom/admin/](http://localhost/bainhom/admin/)

## 🔐 Tài khoản mẫu (Demo)
| Vai trò | Email | Mật khẩu |
| :--- | :--- | :--- |
| **Admin** | `admin@techzone.com` | `123456` |
| **User** | `user@techzone.com` | `123456` |

## ⚠️ Lưu ý
- Nếu ảnh sản phẩm không hiển thị, vui lòng kiểm tra lại đường dẫn/thư mục `assets/images`.
- Nếu giỏ hàng không cập nhật, hãy thử làm mới (reload) lại trang web sau khi thao tác.
