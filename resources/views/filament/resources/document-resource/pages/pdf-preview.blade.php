<div class="w-full">
    @if($pdfUrl)
        <div class="border rounded-lg overflow-hidden" style="height: 400px;">
            <iframe 
                src="{{ route('pdf.viewer', ['file' => basename($pdfUrl)]) }}" 
                width="100%" 
                height="100%" 
                style="border: none;"
                title="PDF Document Preview"
            >
                <p>Your browser does not support PDF viewing. 
                <a href="{{ Storage::disk('public')->url($pdfUrl) }}" target="_blank" class="text-primary-600 hover:text-primary-500">Click here to download the PDF file.</a></p>
            </iframe>
        </div>
        <div class="mt-2 text-sm text-gray-600">
            <a href="{{ Storage::disk('public')->url($pdfUrl) }}" target="_blank" class="text-primary-600 hover:text-primary-500">
                Open PDF in new tab
            </a>
        </div>
    @else
        <div class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No PDF file uploaded</h3>
            <p class="mt-1 text-sm text-gray-500">Upload a PDF file to see preview</p>
        </div>
    @endif
</div>
