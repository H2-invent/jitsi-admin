import { prettyPrintJson } from 'pretty-print-json';

export function initPrettyJson() {
    var jsonField = document.querySelectorAll('.json-beautifier');
    for (const field of jsonField){
        var array = JSON.parse(field.dataset.json);
        var html  = prettyPrintJson.toHtml(array);
        field.innerHTML=html;
    }

}
