$(document).ready(function () {
    var products = $("#products");

    products.ajaxGrid({
        dataUrl: "/products/get",
        sortableColumns: ["name"],
        filterableColumns: ["name", "description"],
        rowsPerPage: 10
    });

});