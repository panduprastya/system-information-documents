<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reject Document - {{ $document->judul_dokumen }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8 bg-white p-8 rounded-lg shadow-lg">
            <div>
                <h2 class="text-center text-3xl font-extrabold text-gray-900">
                    Reject Document
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    {{ $document->judul_dokumen }}
                </p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('documents.reject', $document) }}" class="mt-8 space-y-6">
                @csrf

                <div>
                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700">
                        Alasan Penolakan <span class="text-red-500">*</span>
                    </label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="8" required maxlength="1000"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm"
                        placeholder="Masukkan alasan penolakan dokumen...">{{ old('rejection_reason') }}</textarea>
                    <p class="mt-2 text-sm text-gray-500">
                        Alasan ini akan disimpan sebagai komentar pada dokumen. (Maksimal 1000 karakter)
                    </p>
                </div>

                <div class="flex items-center justify-between space-x-4">
                    <a href="{{ url()->previous() }}"
                        class="flex-1 flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </a>
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menolak dokumen ini?')"
                        class="flex-1 flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Reject Document
                    </button>
                </div>
            </form>

            <div class="mt-6 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Document Information</h3>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Mitra</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $document->mitra->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Upload Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $document->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @if(auth()->user()->hasRole('HSSE'))
                        <div>
                            <dt class="text-sm font-medium text-gray-500">HSSE Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $document->hsse_status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $document->hsse_status === 'pending' ? 'bg-gray-100 text-gray-800' : '' }}
                                        {{ $document->hsse_status === 'reviewing' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $document->hsse_status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $document->hsse_status === 'revisi' ? 'bg-blue-100 text-blue-800' : '' }}">
                                    {{ ucfirst($document->hsse_status) }}
                                </span>
                            </dd>
                        </div>
                    @endif

                    @if(auth()->user()->hasRole('CRM'))
                        <div>
                            <dt class="text-sm font-medium text-gray-500">CRM Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $document->crm_status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $document->crm_status === 'pending' ? 'bg-gray-100 text-gray-800' : '' }}
                                        {{ $document->crm_status === 'reviewing' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $document->crm_status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $document->crm_status === 'revisi' ? 'bg-blue-100 text-blue-800' : '' }}">
                                    {{ ucfirst($document->crm_status) }}
                                </span>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</body>

</html>