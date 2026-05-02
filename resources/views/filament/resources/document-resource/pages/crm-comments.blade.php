<div class="space-y-4">
    @if($comments->isEmpty())
        <div class="text-gray-500 text-center py-4">
            No CRM comments yet.
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
                </div>
            @endforeach
        </div>
    @endif
</div>