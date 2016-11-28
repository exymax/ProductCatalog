(function($) {

    var scope = {
        options: {},
        headers: [],
        model: {},
        sortableColumns: null,
        sortOrder: null,
        allowedOrders: ["asc", "desc"],
        filterableColumns: null,
        filterPattern: null,
        filterLastId: null,
        paginationWrapper: null,
        categoriesWrapper: null,
        categories: null,
        activePage: null,
        rowsWrapper: null,
        gridHeader: null,
        grid: null,
        prefix: '',
        preloader: null,
        adminAccess: false,
        pagesAmount: 0,
        submitButton: null,
        context: null,
        state: {},
        methods: {
            setContext: function(context) {
                scope.context = context;
            },

            initSettings: function() {
                var sortBy = "<option value='none'>no sort</option>",
                    filterBy = "<option value='none'>no filter</option>",
                    header = "";
                for(var i = 0; i < scope.headers.length; i++) {
                    header = scope.headers[i].toLowerCase();
                    if(scope.options.sortableColumns.indexOf(header) >= 0) {
                        sortBy += "<option value='"+header+"'>"+header+"</option>";
                    }
                    if(scope.options.filterableColumns.indexOf(header) >= 0) {
                        filterBy += "<option value='"+header+"'>"+header+"</option>";
                    }
                }
                scope.sortableColumns.html(sortBy);
                scope.filterableColumns.html(filterBy);
                scope.methods.initSelects();
            },

            initSelects: function() {
                scope.selects = scope.context.find(".settings-select");
                scope.selects.each(function() {
                    $(this).material_select();
                });
            },

            initCategories: function() {
                scope.categories.each(function() {
                    $(this).on("click", function(e) {
                        var category = $(this),
                            catID = category.data("category");
                        if(catID === "0") {
                            delete scope.state["category"];
                        }
                        else {
                            scope.state["category"] = catID;
                            scope.methods.categorize(scope.state);
                        }
                        e.stopPropagation();
                        $(".category.active").toggleClass("active");
                        category.toggleClass("active");
                        category.children(".inner-categories").each(function() {
                            $(this).toggleClass("closed");
                        });
                    });
                });
            },

            initGrid: function() {
                scope.context.load("/products/get-template");
                this.get(scope.options.dataUrl, {}, function(response) {
                    scope.adminAccess = response.admin_access;
                    scope.headers = response.headers;
                    scope.model = response.data;
                    scope.gridHeader = scope.context.children(".grid-header");
                    scope.grid = scope.context.children(".grid");
                    scope.preloader = $(".preloader");
                    scope.sortableColumns = scope.context.find("#sort-by-value");
                    scope.sortOrder = scope.context.find("#sort-order-value");
                    scope.filterableColumns = scope.context.find("#filter-field-value");
                    scope.filterPattern = scope.context.find("#filter-pattern");
                    scope.categoriesWrapper = $("#categories");
                    scope.categories = scope.categoriesWrapper.find(".category");
                    scope.submitButton = scope.context.find("#submit-button");
                    scope.prefix = response.prefix || '';
                    scope.paginationWrapper = scope.context.find(".pagination");
                    scope.activePage = scope.paginationWrapper.find("li.active");
                    scope.methods.getPages(scope.options["rowsPerPage"], function() {
                        $('.pagination').twbsPagination({
                            startPage: 1,
                            totalPages: scope.pagesAmount,
                            visiblePages: 7,
                            onPageClick: function (event, page) {
                                scope.state['page'] = page;
                                scope.methods.paginate(scope.state);
                            }
                        });
                    });
                    scope.methods.initSettings();
                    scope.methods.initCategories();
                    scope.methods.handleParamsChange();
                    scope.methods.updateView();
                });
            },

            handleParamsChange: function() {
                scope.methods.handleFiltering();
                scope.methods.handleSorting();
                scope.methods.handlePagination();
            },

            handleFiltering: function() {
                scope.filterableColumns.on("change", function() {
                    if(scope.filterPattern.val()) onFilter();
                });
                scope.filterPattern.on("keypress", function(e) {
                    if(e.keyCode == '13') onFilter();
                });

                function onFilter() {
                    if(!scope.filterPattern.val()) {
                        console.log("incorrect filtering format");
                    }
                    if(scope.filterableColumns.val() === 'none') {
                        delete scope.state["filter_pattern"];
                        delete scope.state["filter_field"];
                        delete scope.state["filter_last_id"];
                        scope.filterPattern.val('');
                        scope.filterableColumns.val('');
                    }
                    else {
                        scope.state["filter_pattern"] = scope.filterPattern.val();
                        scope.state["filter_field"] = scope.filterableColumns.val();

                    }
                    scope.methods.filter(scope.state);
                }
            },

            handleSorting: function() {
                scope.sortOrder.on("change", onSortParamsChange);
                scope.sortableColumns.on("change", onSortParamsChange);

                function onSortParamsChange() {
                    if(scope.allowedOrders.indexOf(scope.sortOrder.val()) < 0) {
                        console.log("incorrect sorting format");
                    }
                    else {
                        scope.state["sort_field"] = scope.sortableColumns.val();
                        scope.state["order_by"] = scope.sortOrder.val();
                        console.log(scope.state);
                        scope.methods.sort(scope.state);
                    }
                }
            },

            handlePagination: function() {
                scope.paginationWrapper.children("li[data-page]").on("click", function() {
                    scope.paginationWrapper.find("li.active").removeClass("active");
                    $(this).addClass("active");
                    scope.methods.paginate();
                });
            },

            sort: function(data) {
                this.process(scope.options.dataUrl, data);
            },

            filter: function(data) {
                this.process(scope.options.dataUrl, data);
            },

            paginate: function(data) {
                this.process(scope.options.dataUrl, data);
            },

            categorize: function(data) {
                this.process(scope.options.dataUrl, data);
            },

            process: function(url, data, callback) {
                scope.paginationWrapper.fadeOut();
                scope.grid.fadeOut(function() {
                    scope.preloader.fadeIn();
                });
                this.get(url, data, function(response) {
                    scope.model = response.data;
                    if(response.filter_last_id) {
                        scope.state['filter_last_id'] = response.filter_last_id;
                    }
                    scope.methods.updateView();
                });
            },

            get: function(url, data, callback) {
                $.ajax({
                    method: "GET",
                    url: url,
                    data: $.extend(data, {
                        'rows_per_page': scope.options['rowsPerPage']
                    }),
                    success: callback
                });
            },

            getPages: function(rows, callback) {
                $.get("/products/get-pages-amount", {
                    'rows_per_page': rows
                }, function(response) {
                    scope.pagesAmount = parseInt(response, 10);
                    callback();
                });
            },

            getEditButton: function(id, a) {
                return a ? '<a class="edit-item btn-floating waves-effect red" href="/admin/product/'+id+'/edit" target="_blank">' +
                    '<i class="large material-icons">mode_edit</i>' +
                    '</a>' : '';
            },

            getPrefix: function() {

            },

            updateView: function() {
                var id, name, imgUrl, description, img, edit,
                    resultDOM = "";
                for (var i = 0; i < scope.model.length; i++) {
                    var row = scope.model[i];
                        edit = scope.methods.getEditButton(row["id"], scope.adminAccess);
                    imgUrl = 'https://d13yacurqjgara.cloudfront.net/users/387195/screenshots/2260293/preview_1x.png';
                    resultDOM += "<div data-id='"+row["id"]+"' class='grid-item col s12 m4 l3 z-depth-1 waves-effect rounded'>";
                    img = "<div class='image-wrapper'>" +
                        "<img src='"+imgUrl+"'>" +
                            edit +
                        "</div>";
                    id = "<div class='prod-info name-id'><b>"+row["id"]+"</b> - "+row["name"]+"</div>";
                    description = "<div class='prod-info description'>"+row["description"]+"</div>";

                    resultDOM += img+id+description/*+year*/;
                    resultDOM += "</div>";
                }
                scope.grid.html(resultDOM).fadeIn();
                scope.paginationWrapper.fadeIn();
                scope.preloader.fadeOut();
                scope.context.find(".grid-item").each(function() {
                    $(this).on("click", function() {
                        window.location.replace("http://127.0.0.1:8000/products/"+$(this).data("id")+"/show");
                    })
                });
            }
        }
    };

    $.fn.ajaxGrid = function(options) {
        options = options || {};
        $.extend(options, {
            dataUrl: "/products/get",
            sortableColumns: ["name"],
            filterableColumns: ["name", "description"],
        });

        scope.options = options;
        scope.methods.setContext(this);
        scope.methods.initGrid();

        return this;
    }

})(jQuery);
