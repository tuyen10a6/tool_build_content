# Content Review Workflow Spec

## 1. Mục tiêu

Bổ sung quy trình kiểm duyệt content với vai trò `reviewer`, hỗ trợ:

- Theo dõi tình trạng content
- Nhận xét/phê duyệt content
- Phân quyền rõ giữa `admin`, `reviewer`, `user`
- Lưu lịch sử kiểm duyệt
- Gửi email thông báo khi content chuyển trạng thái

Spec này dùng làm căn cứ để Codex/Developer chỉnh sửa code trong dự án Laravel `tool_build_content`.

---

## 2. Vai trò người dùng

Hệ thống có 3 loại tài khoản:

- `admin`
- `reviewer`
- `user`

### 2.1 Admin

Admin có toàn quyền:

- Xem toàn bộ content và scene
- Tạo/sửa/xóa content và scene
- Quản lý user
- Cập nhật mọi trạng thái content
- Nhập/chỉnh sửa nhận xét kiểm duyệt
- Xem preview
- Xuất folder
- Xem lịch sử kiểm duyệt

### 2.2 Reviewer

Reviewer chỉ được:

- Xem toàn bộ content và scene
- Xem preview content/scene
- Nhập/chỉnh sửa trường nhận xét/phê duyệt
- Cập nhật trạng thái content thành:
  - `Cần sửa`
  - `Đã duyệt`

Reviewer không được:

- Sửa trực tiếp nội dung content
- Sửa trực tiếp scene
- Sửa ảnh, gif, audio
- Xóa content
- Xuất folder

### 2.3 User

User chỉ được:

- Xem content/scene do chính mình tạo
- Tạo content
- Sửa content/scene của mình khi trạng thái cho phép
- Gửi duyệt
- Xem nhận xét kiểm duyệt

User không được:

- Duyệt content
- Xem toàn bộ content người khác
- Xuất folder

---

## 3. Workflow trạng thái content

### 3.1 Danh sách trạng thái

Trường trạng thái content gồm:

- `Mới`
- `Chờ duyệt`
- `Cần sửa`
- `Đã duyệt`
- `Hoàn thành`

### 3.2 Quy tắc mặc định

- Khi `user` tạo mới content:
  - trạng thái mặc định = `Mới`

### 3.3 Quy tắc chuyển trạng thái

#### User

- `Mới` -> `Chờ duyệt`
- `Cần sửa` -> `Chờ duyệt`

Hành động thực hiện qua nút:

- `Gửi duyệt`

#### Reviewer

- `Chờ duyệt` -> `Cần sửa`
- `Chờ duyệt` -> `Đã duyệt`

Reviewer phải có thể nhập nhận xét trước hoặc cùng lúc cập nhật trạng thái.

#### Admin

Admin được phép cập nhật toàn bộ trạng thái:

- `Mới`
- `Chờ duyệt`
- `Cần sửa`
- `Đã duyệt`
- `Hoàn thành`

### 3.4 Rule chỉnh sửa theo trạng thái

#### User được sửa content/scene khi:

- `Mới`
- `Cần sửa`

#### User không được sửa content/scene khi:

- `Chờ duyệt`
- `Đã duyệt`
- `Hoàn thành`

#### Reviewer không được sửa nội dung content/scene ở mọi trạng thái

Reviewer chỉ được thay đổi:

- `review_comment`
- `approval_status`

---

## 4. Dữ liệu cần bổ sung

### 4.1 Bảng `users`

Yêu cầu:

- Bổ sung/chuẩn hóa trường `email`
- Bổ sung role `reviewer`

#### Field liên quan

- `email`
- `role` với các giá trị:
  - `admin`
  - `reviewer`
  - `user`

### 4.2 Bảng `content_items`

Cần bổ sung các field sau:

- `approval_status` string
- `review_comment` longText nullable
- `reviewed_by` bigint nullable
- `reviewed_by_name` string nullable
- `reviewed_at` datetime nullable
- `submitted_at` datetime nullable
- `revision_requested_count` unsigned integer default 0

### 4.3 Bảng lịch sử kiểm duyệt

Tạo bảng mới, ví dụ `content_review_histories`

#### Fields đề xuất

- `id`
- `content_item_id`
- `from_status`
- `to_status`
- `comment`
- `acted_by`
- `acted_by_name`
- `acted_role`
- `created_at`
- `updated_at` (optional, có thể bỏ nếu không cần)

Mục đích:

- lưu đầy đủ lịch sử đổi trạng thái content
- phục vụ admin audit
- phục vụ timeline ở màn chi tiết content

---

## 5. Phân quyền backend cần thay đổi

Hiện tại hệ thống chủ yếu dùng ownership và `admin`.

Cần mở rộng thành các rule riêng theo action:

### 5.1 View content

- `admin`: xem tất cả
- `reviewer`: xem tất cả
- `user`: chỉ xem content do mình tạo

### 5.2 Edit content body

Bao gồm:

- tên content
- mô tả
- scene
- ảnh
- gif
- audio

Quyền:

- `admin`: được sửa
- `user`: chỉ được sửa content của mình và chỉ khi status là `Mới` hoặc `Cần sửa`
- `reviewer`: không được sửa

### 5.3 Review content

Bao gồm:

- nhập/chỉnh sửa `review_comment`
- đổi `approval_status`

Quyền:

- `admin`: full
- `reviewer`: chỉ được đổi sang `Cần sửa` hoặc `Đã duyệt`
- `user`: không được

### 5.4 Delete content

- `admin`: được
- `user`: theo rule hiện tại nếu nghiệp vụ vẫn cho phép
- `reviewer`: không được

### 5.5 Export folder

- chỉ `admin`

Route export hiện tại phải bị chặn đối với:

- `user`
- `reviewer`

---

## 6. Yêu cầu màn hình danh sách content

Chỉnh từ card view sang table.

### 6.1 Cột cần hiển thị

- Tên content
- Danh mục
- Số phân cảnh
- Người tạo
- Người duyệt
- Số lần yêu cầu chỉnh sửa
- Ngày tạo
- Ngày cập nhật gần nhất
- Tình trạng

### 6.2 Bộ lọc

Giữ filter hiện có và bổ sung:

- `approval_status`

Các filter chính:

- user tạo
- từ ngày
- đến ngày
- tình trạng content

### 6.3 Nút thao tác nhanh

#### Reviewer và Admin

- Xem preview
- Nhận xét
- Yêu cầu sửa
- Duyệt

#### Admin only

- Xuất folder

#### User

- Xem chi tiết
- Gửi duyệt nếu status là `Mới` hoặc `Cần sửa`

---

## 7. Yêu cầu màn hình chi tiết content

Màn chi tiết content phải hiển thị:

- Thông tin content
- Danh sách phân cảnh
- Preview content
- Tình trạng hiện tại
- Nội dung nhận xét/phê duyệt
- Lịch sử kiểm duyệt

### 7.1 Reviewer/Admin

Phải có form:

- nhập nhận xét
- bấm `Cần sửa`
- bấm `Đã duyệt`

Reviewer không được thấy hoặc không được dùng các nút sửa nội dung content/scene.

### 7.2 User

Phải:

- xem được nhận xét từ reviewer/admin
- sửa lại content/scene khi status là `Cần sửa`
- có nút `Gửi duyệt lại`

Nếu status là `Chờ duyệt`, `Đã duyệt`, `Hoàn thành`:

- content hiển thị readonly

---

## 8. Email thông báo

### 8.1 Khi user gửi duyệt

Điều kiện:

- status chuyển sang `Chờ duyệt`

Hành động:

- gửi email đến các tài khoản `reviewer` đang `active`

Nội dung:

- có content mới cần duyệt
- tên content
- người tạo
- link vào chi tiết content

### 8.2 Khi reviewer cập nhật kết quả duyệt

Nếu reviewer đổi sang:

- `Cần sửa`
- `Đã duyệt`

Hành động:

- gửi email cho user tạo content

Nội dung:

- content nào đã được duyệt / yêu cầu sửa
- nhận xét
- link vào chi tiết content

### 8.3 Yêu cầu kỹ thuật

- dùng queue cho email nếu có thể
- không hard-code email
- chỉ gửi cho user `active`

---

## 9. Menu lịch sử kiểm duyệt content

Chỉ admin xem được.

Màn này cần hiển thị:

- Người cập nhật
- Thời điểm cập nhật
- Trạng thái trước
- Trạng thái sau
- Nội dung nhận xét

Nguồn dữ liệu:

- bảng `content_review_histories`

---

## 10. Tác động vào code hiện tại

### 10.1 Model cần sửa

- `app/Models/User.php`
- `app/Models/ContentItem.php`
- tạo model mới cho `ContentReviewHistory`

### 10.2 Controller cần sửa

- `app/Http/Controllers/ContentController.php`
- `app/Http/Controllers/SceneController.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Controllers/ExportController.php`
- `app/Http/Controllers/Controller.php`

Có thể cần controller mới:

- `ContentReviewController`
- `ContentReviewHistoryController`

### 10.3 View cần sửa

- `resources/views/contents/index.blade.php`
- `resources/views/contents/show.blade.php`
- `resources/views/users/index.blade.php`
- thêm view cho history nếu tách menu riêng

### 10.4 Route cần sửa

- thêm route gửi duyệt
- thêm route reviewer duyệt / yêu cầu sửa
- thêm route admin xem lịch sử duyệt
- chỉnh quyền route export content/folder thành admin only

---

## 11. Quy tắc kỹ thuật quan trọng

### 11.1 Không trust frontend

Frontend không được tự set:

- `approval_status`
- `reviewed_by`
- `reviewed_by_name`
- `reviewed_at`
- `revision_requested_count`
- `role`

Backend phải tự kiểm tra và tự gán.

### 11.2 Mọi đổi trạng thái phải ghi lịch sử

Khi đổi `approval_status`, phải insert bản ghi vào `content_review_histories`.

### 11.3 Reviewer không được sửa nội dung

Backend phải chặn rõ ở controller/policy, không chỉ ẩn nút ngoài giao diện.

### 11.4 Export folder chỉ admin

Không chỉ ẩn nút ở UI.

Route và controller export phải chặn reviewer/user bằng backend.

---

## 12. Đề xuất triển khai theo thứ tự

### Phase 1

- Migration users/content/history
- Update models

### Phase 2

- Update role/permission helpers
- Chặn export cho reviewer/user

### Phase 3

- Workflow status content
- Form gửi duyệt / duyệt / yêu cầu sửa
- Ghi lịch sử kiểm duyệt

### Phase 4

- Refactor màn list content sang dạng bảng
- Bổ sung filter status
- Bổ sung action nhanh

### Phase 5

- Email notification
- Màn lịch sử kiểm duyệt cho admin

---

## 13. Acceptance criteria

### User

- tạo content mới có trạng thái `Mới`
- sửa được content/scene khi `Mới` hoặc `Cần sửa`
- gửi duyệt được
- xem được nhận xét reviewer/admin
- không export folder

### Reviewer

- xem được toàn bộ content/scene
- preview được
- nhập nhận xét được
- cập nhật `Cần sửa` hoặc `Đã duyệt`
- không sửa nội dung
- không xóa content
- không export folder

### Admin

- quản lý toàn bộ user/content
- cập nhật được mọi trạng thái
- export folder được
- xem lịch sử kiểm duyệt được

### System

- mọi thay đổi trạng thái đều có log
- có email thông báo đúng người nhận
- backend chặn quyền đúng theo role

