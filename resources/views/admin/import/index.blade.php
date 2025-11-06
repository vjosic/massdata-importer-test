@extends('layouts.adminlte')

@section('title', 'Data Import')
@section('page_title', 'Data Import')
@section('page_description', 'Import data from Excel or CSV files')

@section('breadcrumb')
    <li class="active">Data Import</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Import Data</h3>
            </div>
            <!-- /.box-header -->
            
            <!-- Error Messages -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <h4><i class="icon fa fa-ban"></i> Import Error!</h4>
                    <ul style="margin-bottom: 0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form method="POST" action="{{ route('admin.import.upload') }}" enctype="multipart/form-data" id="importForm">
                @csrf
                <div class="box-body">
                    <!-- Import Type Selection -->
                    <div class="form-group @error('import_type') has-error @enderror">
                        <label for="import_type">Import Type</label>
                        <select class="form-control" id="import_type" name="import_type" required>
                            <option value="">Select what you want to import...</option>
                            @foreach($availableImports as $key => $config)
                                <option value="{{ $key }}" {{ old('import_type') == $key ? 'selected' : '' }}>
                                    {{ $config['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('import_type')
                            <span class="help-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- File Upload Section -->
                    <div id="file-upload-section" style="display: none;">
                        <div id="file-inputs">
                            <!-- File inputs will be dynamically generated here -->
                        </div>
                    </div>

                    <!-- Required Headers Section -->
                    <div id="required-headers-section" style="display: none;">
                        <div class="box box-info">
                            <div class="box-header with-border">
                                <h3 class="box-title"><i class="fa fa-info-circle"></i> Required Headers</h3>
                            </div>
                            <div class="box-body">
                                <p>Make sure your file(s) contain the following headers:</p>
                                <div id="required-headers-content">
                                    <!-- Headers will be dynamically loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.box-body -->

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary" id="import-btn" disabled>
                        <i class="fa fa-upload"></i> Start Import
                    </button>
                    <button type="button" class="btn btn-default" onclick="resetForm()">
                        <i class="fa fa-refresh"></i> Reset
                    </button>
                </div>
            </form>
        </div>
        <!-- /.box -->
    </div>
</div>
@endsection

@push('js')
<style>
#required-headers-section .box {
    background: linear-gradient(135deg, #e3f2fd 0%, #f1f8e9 100%);
    border: 1px solid #81c784;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#required-headers-section .box-header {
    background: linear-gradient(135deg, #4caf50 0%, #2196f3 100%);
    color: white;
    border-bottom: 1px solid #45a049;
}

#required-headers-section .box-title {
    color: white !important;
    font-weight: bold;
}

#required-headers-section ul {
    background: rgba(255, 255, 255, 0.7);
    padding: 10px 15px;
    border-radius: 4px;
    border-left: 4px solid #4caf50;
    margin-bottom: 15px;
}

#required-headers-section li {
    color: #333;
    margin-bottom: 5px;
}

#required-headers-section .text-red {
    color: #f44336 !important;
    font-weight: bold;
}
</style>
<script>
$(document).ready(function() {
    let ajaxInProgress = false;
    
    $('#import_type').change(function() {
        const importType = $(this).val();
        
        if (ajaxInProgress) {
            return;
        }
        
        if (!importType) {
            resetForm();
            return;
        }

        ajaxInProgress = true;
        
        $.ajax({
            url: '{{ route('admin.import.config') }}',
            method: 'GET',
            data: { type: importType },
            success: function(response) {
                ajaxInProgress = false;
                
                if (response.error) {
                    alert('Error: ' + response.error);
                    return;
                }
                
                // Show file upload section
                $('#file-upload-section').show();
                
                // Generate file inputs
                let fileInputsHtml = '';
                const files = response.config.files;
                
                for (const [fileKey, fileConfig] of Object.entries(files)) {
                    const isRequired = fileConfig.required ? 'required' : '';
                    const requiredText = fileConfig.required ? ' (Required)' : ' (Optional)';
                    
                    fileInputsHtml += `
                        <div class="form-group">
                            <label for="${fileKey}">${fileConfig.label}${requiredText}</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="${fileKey}" 
                                   name="files[${fileKey}]" 
                                   accept=".csv,.xlsx,.xls"
                                   ${isRequired}>
                            <p class="help-block">Accepted formats: CSV, Excel (.xlsx, .xls)</p>
                        </div>
                    `;
                }
                
                $('#file-inputs').html(fileInputsHtml);
                
                // Show required headers section
                if (response.config.headers && response.config.headers.length > 0) {
                    $('#required-headers-section').show();
                    
                    let headersHtml = '<ul>';
                    response.config.headers.forEach(function(header) {
                        const requiredClass = header.required ? 'text-red' : '';
                        const requiredText = header.required ? ' (Required)' : ' (Optional)';
                        headersHtml += `<li class="${requiredClass}"><strong>${header.name}</strong>${requiredText}`;
                        if (header.description) {
                            headersHtml += ` - ${header.description}`;
                        }
                        headersHtml += '</li>';
                    });
                    headersHtml += '</ul>';
                    
                    $('#required-headers-content').html(headersHtml);
                } else {
                    $('#required-headers-section').hide();
                }
                
                // Enable import button when at least one file is selected
                updateImportButton();
                
                // Add change listener to file inputs
                $(document).off('change', 'input[type="file"]').on('change', 'input[type="file"]', function() {
                    updateImportButton();
                });
            },
            error: function(xhr) {
                ajaxInProgress = false;
                alert('Error loading import configuration: ' + xhr.responseText);
            }
        });
    });

    // Form submission validation
    $('#importForm').on('submit', function(e) {
        const selectedFiles = $('input[type="file"]').filter(function() {
            return $(this).val() !== '';
        });
        
        if (selectedFiles.length === 0) {
            e.preventDefault();
            alert('Please select at least one file to upload.');
            return false;
        }
        
        // Show loading state
        const $btn = $('#import-btn');
        $btn.prop('disabled', true);
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Processing...');
    });
});

function updateImportButton() {
    const selectedFiles = $('input[type="file"]').filter(function() {
        return $(this).val() !== '';
    });
    
    if (selectedFiles.length > 0) {
        $('#import-btn').prop('disabled', false);
    } else {
        $('#import-btn').prop('disabled', true);
    }
}

function resetForm() {
    // Reset form
    $('#importForm')[0].reset();
    
    // Hide sections
    $('#file-upload-section').hide();
    $('#required-headers-section').hide();
    
    // Clear file inputs
    $('#file-inputs').empty();
    
    // Clear headers content
    $('#required-headers-content').empty();
    
    // Disable import button
    $('#import-btn').prop('disabled', true);
    
    // Reset import button text
    $('#import-btn').html('<i class="fa fa-upload"></i> Start Import');
    
    // Remove any alert messages
    $('.alert').remove();
    
    // Reset any validation states
    $('.form-group').removeClass('has-error');
    $('.help-block').not('.permanent-help').remove();
}
</script>
@endpush