@push('after_scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Find fields
        const baseCommandField = document.querySelector('select[name="base_command"]');
        const paramsField = document.querySelector('input[name="command_params"]');
        const fullCommandField = document.querySelector('input[name="command"]');
        
        // Function to update the complete command
        function updateFullCommand() {
            const baseCommand = baseCommandField.value || '';
            const params = paramsField.value || '';
            
            // Split the base_command value, because it may contain a description
            const justCommand = baseCommand.split(' - ')[0].trim();
            
            fullCommandField.value = justCommand + (params ? ' ' + params : '');
        }
        
        // Listen for changes in fields
        if (baseCommandField) {
            baseCommandField.addEventListener('change', updateFullCommand);
        }
        
        if (paramsField) {
            paramsField.addEventListener('input', updateFullCommand);
        }
        
        // Initialize on load
        if (baseCommandField && paramsField && fullCommandField) {
            updateFullCommand();
        }
        
        // When editing, split the value between two fields
        if (fullCommandField.value) {
            const parts = fullCommandField.value.split(' ');
            const baseCmd = parts[0];
            const params = parts.slice(1).join(' ');
            
            // Find the correct option in the select (may contain description)
            if (baseCommandField.options) {
                for (let i = 0; i < baseCommandField.options.length; i++) {
                    const optionText = baseCommandField.options[i].text;
                    if (optionText.startsWith(baseCmd + ' ') || optionText === baseCmd) {
                        baseCommandField.selectedIndex = i;
                        break;
                    }
                }
            }
            
            // Set parameters
            paramsField.value = params;
        }
    });
</script>
@endpush
