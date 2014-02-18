require(["sbt/dom", "sbt/connections/controls/profiles/ProfileGrid"], function(dom, ProfileGrid) {
    var grid = new ProfileGrid({
        type : "reportingChain",
        userid : "%{name=sample.userId1|helpSnippetId=Social_Profiles_Get_Profile}",
        theme: "bootstrap",
        hidePager:true,
        hideSorter:true,
        hideFooter: true
    });

    dom.byId("gridDiv").appendChild(grid.domNode);

    grid.update();
});
