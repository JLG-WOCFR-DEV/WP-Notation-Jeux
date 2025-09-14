jQuery(document).ready(function($) {
    
    // S'applique à toutes les tables avec la classe 'jlg-summary-table'
    $('table.jlg-summary-table th.sortable').on('click', function() {
        var table = $(this).parents('table').eq(0);
        var rows = table.find('tbody tr').toArray().sort(comparer($(this).index()));
        
        var isAsc = !($(this).hasClass('sorted-asc'));
        if (!isAsc) {
            rows = rows.reverse();
        }

        table.find('th.sortable').removeClass('sorted-asc sorted-desc');
        $(this).addClass(isAsc ? 'sorted-asc' : 'sorted-desc');

        for (var i = 0; i < rows.length; i++) {
            table.append(rows[i]);
        }
    });

    /**
     * Crée une fonction de comparaison pour la méthode sort().
     * @param {number} index L'index de la colonne sur laquelle trier.
     * @returns Une fonction de comparaison.
     */
    function comparer(index) {
        return function(a, b) {
            var valA = getCellValue(a, index);
            var valB = getCellValue(b, index);
            
            // Tente une comparaison numérique d'abord, sinon textuelle
            return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB.toString());
        };
    }

    /**
     * Récupère la valeur d'une cellule de tableau.
     * @param {HTMLElement} row La ligne (tr).
     * @param {number} index L'index de la cellule (td).
     * @returns La valeur textuelle de la cellule.
     */
    function getCellValue(row, index) {
        return $(row).children('td').eq(index).text();
    }
});