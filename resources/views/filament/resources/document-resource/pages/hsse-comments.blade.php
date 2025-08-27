<div class="space-y-4">
    @if($comments->isEmpty())
        <div class="text-gray-500 text-center py-4">
            No HSSE comments yet.
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
                    
                    {{-- @if($comment->status_before || $comment->status_after)
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Status: 
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $comment->status_before === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $comment->status_before === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $comment->status_before === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $comment->status_before === 'reviewing' ? 'bg-blue-100 text-blue-800' : '' }}">
                                {{ $comment->status_before }}
                            </span>
                        </div>
                    @endif --}}
                </div>
            @endforeach
        </div>
    @endif
</div>
