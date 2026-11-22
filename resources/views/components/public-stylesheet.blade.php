@props(['file'])
<link rel="stylesheet" href="{{ asset('assets/'.$file) }}?v={{ filemtime(public_path('assets/'.$file)) }}">
