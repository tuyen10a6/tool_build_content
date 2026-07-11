<p>Xin chào,</p>

<p>Có content mới cần duyệt.</p>

<p><strong>Tên content:</strong> {{ $contentItem->name }}</p>
<p><strong>Người tạo:</strong> {{ $contentItem->created_by_name ?: 'Không rõ' }}</p>
<p><strong>Link chi tiết:</strong> <a href="{{ $contentUrl }}">{{ $contentUrl }}</a></p>
