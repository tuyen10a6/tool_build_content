<p>Xin chào,</p>

<p>Content của bạn vừa được cập nhật kết quả duyệt.</p>

<p><strong>Tên content:</strong> {{ $contentItem->name }}</p>
<p><strong>Kết quả:</strong> {{ $statusLabel }}</p>
<p><strong>Nhận xét:</strong> {{ $reviewComment ?: 'Không có nhận xét.' }}</p>
<p><strong>Link chi tiết:</strong> <a href="{{ $contentUrl }}">{{ $contentUrl }}</a></p>
