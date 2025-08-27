<div class="space-y-4">
    @if($comments->isEmpty())
        <div class="text-gray-500 text-center py-4">
            No S&D comments yet.
        </div>
    @else
        <div class="space-y-3">
            @foreach($comments as $comment)
                <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                    <div class="flex justify-between items-start mb-2">
                        <div class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $comment->user->name ?? 'Unknown User' }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $comment->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    
                    <div class="text-gray-700 dark:text-gray-300 mb-2">
                        {{ $comment->komentar }}
                    </div>
                    
                    @if($comment->notes_reference || $comment->notes_line_number || $comment->notes_excerpt)
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            @if($comment->notes_reference)
                                <div>Reference: {{ $comment->notes_reference }}</div>
                            @endif
                            @if($comment->notes_line_number)
                                <div>Line: {{ $comment->notes_line_number }}</div>
                            @endif
                            @if($comment->notes_excerpt)
                                <div>Excerpt: {{ Str::limit($comment->notes_excerpt, 100) }}</div>
                            @endif
                        </div>
                    @endif
                    
                    @if($comment->is_resolved)
                        <div class="mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Resolved
                            </span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
