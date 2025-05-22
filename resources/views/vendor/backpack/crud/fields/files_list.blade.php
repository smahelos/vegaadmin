@php
$field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
$field['wrapper']['class'] = $field['wrapper']['class'] ?? "form-group col-sm-12";
$field['wrapper']['data-init-function'] = $field['wrapper']['data-init-function'] ?? 'bpFieldInitFilesList';
$field['wrapper']['data-field-name'] = $field['wrapper']['data-field-name'] ?? $field['name'];
@endphp

@include('crud::fields.inc.wrapper_start')
<label>{!! $field['label'] !!}</label>
@include('crud::fields.inc.translatable_icon')

{{-- HINT --}}
@if (isset($field['hint']))
<p class="help-block">{!! $field['hint'] !!}</p>
@endif

{{-- Multiple files upload input --}}
<div class="file-upload-area mb-3">
    <div class="d-flex flex-column">
        <div class="custom-file-input-wrapper">
            <input type="file" name="{{ $field['name'] }}[]" id="{{ $field['name'] }}_input"
                @include('crud::fields.inc.attributes', ['default_class'=> 'files-input form-control'])
            multiple
            accept="{{ $field['accept'] ?? '' }}"
            style="display: none;">
            <label for="{{ $field['name'] }}_input" class="btn btn-outline-primary upload-button">
                <i class="la la-cloud-upload-alt"></i> {{ trans('admin.global.upload_files') }}
            </label>
        </div>

        {{-- Files upload progress --}}
        <div class="progress mt-2" style="display: none;" id="{{ $field['name'] }}_progress_area">
            <div class="progress-bar" role="progressbar" style="width: 0%;" id="{{ $field['name'] }}_progress"></div>
        </div>
    </div>
</div>

{{-- Files container --}}
<div class="files-container">
    {{-- List of existing and newly uploaded files --}}
    <div class="files-list mb-3" id="{{ $field['name'] }}_list">
        @if (isset($entry) && $entry->hasAttribute($field['name']) && is_array($entry->{$field['name']}))
        @foreach ($entry->{$field['name']} as $key => $file)
        <div class="file-item mb-2 d-flex align-items-center p-2 border rounded">
            @php
            $fileUrl = method_exists($entry, 'getFileUrl') ? $entry->getFileUrl($field['name'], $key) : asset('storage/'
            . $file);
            $fileService = app(\App\Services\FileUploadService::class);
            $fileIcon = $fileService->getFileTypeIcon($file);
            $fileName = basename($file);
            $fileSize = Storage::disk('public')->exists($file) ? Storage::disk('public')->size($file) : 0;
            $fileSizeKB = round($fileSize / 1024, 2);
            @endphp

            <i class="la {{ $fileIcon }} nav-icon la-2x mr-4"></i>
            <div class="file-details flex-grow-1">
                <a href="{{ $fileUrl }}" target="_blank"
                    class="file-name font-weight-bold text-gray-500 hover:text-gray-800">{{ $fileName }}</a>
                <span class="text-muted ml-4">({{ $fileSizeKB }} KB)</span>
            </div>
            <input type="hidden" name="{{ $field['name'] }}_hidden[]" value="{{ $file }}">
            <button type="button" class="btn btn-sm btn-link remove-file text-danger"
                title="{{ trans('admin.expenses.remove_file') }}">
                <i class="la la-trash"></i>
            </button>
        </div>
        @endforeach
        @endif
    </div>
</div>

{{-- Hidden field for tracking removed files --}}
<input type="hidden" name="{{ $field['name'] }}_removed" id="{{ $field['name'] }}_removed" value="">

@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')
<style>
    .file-upload-area {
        border: 2px dashed #ccc;
        padding: 20px;
        text-align: center;
        background-color: #f8f9fa;
        border-radius: 4px;
        transition: all 0.3s;
    }

    .file-upload-area.active {
        border-color: #4CAF50;
        background-color: #f0f9f0;
    }

    .upload-button {
        display: inline-block;
        cursor: pointer;
    }

    .file-item {
        transition: all 0.2s;
    }

    .file-item:hover {
        background-color: #f8f9fa;
    }

    .progress {
        height: 10px;
        transition: all 0.3s;
    }
</style>
@endpush

@push('crud_fields_scripts')
<script>
    function bpFieldInitFilesList(element) {
        // Zajistíme, že element je skutečně DOM element
        if (typeof element === 'string') {
            element = document.querySelector(`[data-field-name="${element}"]`);
        }
        
        // Pro případ, že element je jQuery objekt
        if (element.jquery) {
            element = element[0];
        }
        
        // Kontrola, zda element existuje a má požadovaný atribut
        if (!element || typeof element.getAttribute !== 'function') {
            console.error('Invalid element passed to bpFieldInitFilesList');
            return;
        }

        const fieldName = element.getAttribute('data-field-name');
        const filesInput = document.getElementById(`${fieldName}_input`);
        const filesList = document.getElementById(`${fieldName}_list`);
        const removedFiles = document.getElementById(`${fieldName}_removed`);
        const uploadArea = filesInput.closest('.file-upload-area');
        const progressBar = document.getElementById(`${fieldName}_progress`);
        const progressArea = document.getElementById(`${fieldName}_progress_area`);
        const removedFilesArray = [];
        
        // Setup drag and drop area
        if (uploadArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.classList.add('active');
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.classList.remove('active');
                }, false);
            });
            
            uploadArea.addEventListener('drop', (e) => {
                const droppedFiles = e.dataTransfer.files;
                filesInput.files = droppedFiles;
                
                // Trigger change event
                const event = new Event('change', { bubbles: true });
                filesInput.dispatchEvent(event);
            }, false);
        }
        
        // Add new files when selected
        if (filesInput) {
            filesInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    // Show progress area when files are selected
                    progressArea.style.display = 'block';
                    progressBar.style.width = '0%';
                    
                    // Simulate progress (in a real app, this would track actual upload progress)
                    let progress = 0;
                    const interval = setInterval(() => {
                        progress += 5;
                        progressBar.style.width = `${progress}%`;
                        progressBar.setAttribute('aria-valuenow', progress);
                        
                        if (progress >= 100) {
                            clearInterval(interval);
                            
                            // Hide progress after completion
                            setTimeout(() => {
                                progressArea.style.display = 'none';
                            }, 1000);
                        }
                    }, 50);
                    
                    Array.from(e.target.files).forEach(file => {
                        const reader = new FileReader();
                        reader.onload = function() {
                            // Determine file icon class based on type
                            let iconClass = 'la-file';

                            if (file.type.startsWith('image/')) {
                                iconClass = 'la-file-image';
                            } else if (file.type === 'application/pdf') {
                                iconClass = 'la-file-pdf';
                            } else if (file.type.includes('word') || file.name.match(/\.(doc|docx)$/i)) {
                                iconClass = 'la-file-word';
                            } else if (file.type.includes('excel') || file.type.includes('spreadsheet') || file.name.match(/\.(xls|xlsx|csv)$/i)) {
                                iconClass = 'la-file-excel';
                            } else if (file.type.includes('text') || file.name.match(/\.(txt|md)$/i)) {
                                iconClass = 'la-file-alt';
                            } else if (file.type.includes('zip') || file.type.includes('archive') || file.name.match(/\.(zip|rar|7z|tar|gz)$/i)) {
                                iconClass = 'la-file-archive';
                            } else if (file.type.includes('audio') || file.name.match(/\.(mp3|wav|ogg)$/i)) {
                                iconClass = 'la-file-audio';
                            } else if (file.type.includes('video') || file.name.match(/\.(mp4|avi|mov|wmv|mkv)$/i)) {
                                iconClass = 'la-file-video';
                            } else if (file.type.includes('code') || file.name.match(/\.(html|css|js|php|py|java|xml|json)$/i)) {
                                iconClass = 'la-file-code';
                            }

                            const fileItem = document.createElement('div');
                            fileItem.className = 'file-item mb-2 d-flex align-items-center p-2 border rounded';
                            fileItem.innerHTML = `
                                <i class="la ${iconClass} la-2x mr-2"></i>
                                <div class="file-details flex-grow-1">
                                    <span class="file-name font-weight-bold">${file.name}</span>
                                    <span class="text-muted ml-2">(${(file.size / 1024).toFixed(2)} KB)</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-link remove-file text-danger" title="{{ trans('admin.expenses.remove_file') }}">
                                    <i class="la la-trash"></i>
                                </button>
                            `;
                            
                            // Add remove button event
                            const removeBtn = fileItem.querySelector('.remove-file');
                            removeBtn.addEventListener('click', function() {
                                fileItem.remove();
                            });
                            
                            // Add animation for new items
                            fileItem.style.opacity = '0';
                            filesList.appendChild(fileItem);
                            
                            // Fade in animation
                            setTimeout(() => {
                                fileItem.style.opacity = '1';
                            }, 10);
                        };
                        reader.readAsDataURL(file);
                    });
                }
            });
        }
        
        // Add event listeners to existing remove buttons
        document.querySelectorAll(`#${fieldName}_list .remove-file`).forEach(button => {
            button.addEventListener('click', function() {
                const fileItem = this.closest('.file-item');
                const hiddenInput = fileItem.querySelector('input[type="hidden"]');
                
                if (hiddenInput) {
                    // Add this file to the removed list
                    removedFilesArray.push(hiddenInput.value);
                    removedFiles.value = JSON.stringify(removedFilesArray);
                }
                
                // Fade out animation before removing
                fileItem.style.opacity = '0';
                setTimeout(() => {
                    fileItem.remove();
                }, 300);
            });
        });
    }
</script>
@endpush
