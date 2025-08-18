## Hướng dẫn thao tác để sử dụng các tính năng tài chính 
1. Cài đặt thư viện composer (trong thư mục dự án chứa composer.json), chạy:
composer require vlucas/phpdotenv

2. Tạo file .env (thư mục gốc, cùng cấp db.php)
DB_HOST=your-db-host
DB_PORT=3306
DB_NAME=your-db-name
DB_USER=your-db-user
DB_PASS=your-db-password
DB_CHARSET=utf8mb4

3. Đảm bảo db.php đọc cấu hình từ .env (đãcung cấp trong dự án). 
Các file PHP khác chỉ cần: require_once __DIR__ . '/db.php';

4. Cấu hình AI: Tạo file chatgpt_api_token.json để bật các tính năng nhận diện giáo viên /tạm ứng /"thu & giữ"
{
    "api_key": "sk-xxx_your_openai_api_key"
}
Liên hệ admin để nhận key API

5. (Tùy chọn) Tùy chỉnh Settings
Sau khi chạy ứng dụng, mở "Settings" trong giao diện để:
- Nhập phần trăm Center Fee,
- Danh sách Teacher Names (mỗi dòng 1 tên, dùng cho AI),
- Tùy chỉnh Prompt Templates.
Dữ liệu sẽ tự động lưu vào finance_settings.json

Cấu trúc thư mục gợi ý:
project/
├─ db.php                       
├─ .env                         
├─ finance_settings.json        
├─ chatgpt_api_token.json       
├─ export_logic_core.php        
├─ export_logic.php              
├─ finance_class_report.php     
├─ finance_report.php           
├─ daily_account.php         
└─ VanHoa-README.md

Biến	    Ý nghĩa	        Mặc định
DB_HOST	    Host DB	        127.0.0.1
DB_PORT	    Port DB	        3306
DB_NAME	    Tên database	(bắt buộc)
DB_USER	    User	        (bắt buộc)
DB_PASS	    Mật khẩu	    (trống)
DB_CHARSET	Charset	        utf8mb4

KHÔNG commit file .env và các file có secret (API key)
Thêm vào .gitignore:
.env
finance_settings.json
chatgpt_api_token.json
*.xlsx
vendor/
.idea/
.vscode/