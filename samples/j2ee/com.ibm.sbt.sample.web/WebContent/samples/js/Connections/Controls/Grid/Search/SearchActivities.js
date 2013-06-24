require(["sbt/dom", "sbt/connections/controls/search/SearchGrid"], function(dom, SearchGrid) {
        var grid = new SearchGrid({
             type : "activities",
             query : "activity"
        });

        dom.byId("gridDiv").appendChild(grid.domNode);

        grid.update();
});