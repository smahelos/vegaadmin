@push('after_scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Najdeme pole
        const baseCommandField = document.querySelector('select[name="base_command"]');
        const paramsField = document.querySelector('input[name="command_params"]');
        const fullCommandField = document.querySelector('input[name="command"]');
        
        // Funkce pro aktualizaci kompletního příkazu
        function updateFullCommand() {
            const baseCommand = baseCommandField.value || '';
            const params = paramsField.value || '';
            
            // Rozdělíme hodnotu base_command, protože může obsahovat i popis
            const justCommand = baseCommand.split(' - ')[0].trim();
            
            fullCommandField.value = justCommand + (params ? ' ' + params : '');
        }
        
        // Nasloucháme změnám v polích
        if (baseCommandField) {
            baseCommandField.addEventListener('change', updateFullCommand);
        }
        
        if (paramsField) {
            paramsField.addEventListener('input', updateFullCommand);
        }
        
        // Inicializace při načtení
        if (baseCommandField && paramsField && fullCommandField) {
            updateFullCommand();
        }
        
        // Při editaci rozdělíme hodnotu mezi dvě pole
        if (fullCommandField.value) {
            const parts = fullCommandField.value.split(' ');
            const baseCmd = parts[0];
            const params = parts.slice(1).join(' ');
            
            // Najdeme správnou option v selectu (může obsahovat popis)
            if (baseCommandField.options) {
                for (let i = 0; i < baseCommandField.options.length; i++) {
                    const optionText = baseCommandField.options[i].text;
                    if (optionText.startsWith(baseCmd + ' ') || optionText === baseCmd) {
                        baseCommandField.selectedIndex = i;
                        break;
                    }
                }
            }
            
            // Nastavíme parametry
            paramsField.value = params;
        }
    });
</script>
@endpush
