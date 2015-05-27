var ActiveEdits = function() {

    var DOM = {};
    var options = {};
    var timers = {};
    var taggableRecord = null;
    var formChanged = false;
    var activeEditNode = null;
    var loadDate = null;
    var listTitleIndex = 1;
    var dateRegEx = /(\d\d\d\d)-(\d\d)-(\d\d)T(\d\d):(\d\d):(\d\d)([+-])(\d\d):(\d\d)/;
    var nodeService = NodeService.getInstance();

    var _setOptions = function(_options) {
        options = $.extend({
            AddNonce : null,
            DeleteNonce : null,
            UpdateMetaNonce : null,
            ServerDate : null,
            HeartbeatFrequency : 60,
            StaleActiveEditThreshold : 45,
            MaxActiveEdits : 50,
            ListCheckFrequency : 30
        },_options || {});
    };

    var _initList = function(_options) {

        _setOptions(_options);

        var $body = $('body');

        if(!$body.hasClass('list') || $body.hasClass('signin')) return;

        if(!SystemService.aspectsShareElements(List.getAspect(),'mixin-track-active-edits')) return;

        $('#app-content table.data tr:eq(0) th').each(function(i,e){
            e = $(e);
            if(e.text().toUpperCase() == 'TITLE')
                listTitleIndex = i;
        });

        DOM.tooltipPanel = $('<div id="active-edit-tooltip" style="display:none"></div>');
        DOM.tooltipList = $('<ul></ul>');

        DOM.tooltipPanel.append(DOM.tooltipList);

        $('body').append(DOM.tooltipPanel);

        setTimeout(function(){
            _refreshList();
            setInterval(_refreshList,options.ListCheckFrequency*1000);
        },1000);
    };

    var _refreshList = function() {

        var nq = new NodeQuery({
            'Elements.in' : '@active-edits',
            'Status.eq' : 'draft',
            'OutTags.select' : '#active-edit-record,#active-edit-member',
            'Meta.select' : '#form-changed',
            'OutTags.exist' : '#active-edit-record',
            'ActiveDate.after' : (-1*options.StaleActiveEditThreshold)+' seconds',
            'Keys' : 'Cheaters'
        },{
            CreationDate : 'DESC'
        },options.MaxActiveEdits,1);

        nodeService.findAll(nq,{
            success : function(nodeQuery) {

                var spans = $('#app-content table.data tr td span.active-edit-count');

                spans.each(function(i,e){
                    $(e).text(0).removeClass('frm-chng').data('memberList',{});
                });

                $('#app-content table.data tr').each(function(i,e){

                    var tr = $(e);
                    var td = tr.children('td:eq('+listTitleIndex+')');
                    var span = $('span.active-edit-count',td);

                    $(nodeQuery.getResults()).each(function(i,node){

                        var slug = node.Cheaters['#active-edit-record'].TagSlug;
                        var element = node.Cheaters['#active-edit-record'].TagElement;

                        if(tr.data('Slug') == slug && tr.data('ElementSlug') == element) {

                            //console_log(node.Cheaters['#active-edit-member'].TagLinkTitle);

                            if(span.length == 0) {
                                span = $('<span class="active-edit-count" style="display:none">'+1+'</span>');
                                td.prepend(span);
                                span.fadeIn(2000);
                            } else {
                                span.text(parseInt(span.text())+1);
                            }


                            var memberList = span.data('memberList');
                            if(memberList && memberList[node.Cheaters['#active-edit-member'].TagSlug]) {
                                memberList[node.Cheaters['#active-edit-member'].TagSlug].Count++;
                            } else {
                                memberList = {};
                                memberList[node.Cheaters['#active-edit-member'].TagSlug] = {
                                    Title : node.Cheaters['#active-edit-member'].TagLinkTitle,
                                    Count : 1
                                };
                            }
                            span.data('memberList',memberList);


                            if(node.Cheaters['#form-changed'].MetaValue == '1')
                                span.addClass('frm-chng');
                        }
                    });

                    if(span.length > 0) {
                        span.unbind().hover(function(){
                            _showTooltip(span.data('memberList'),span);
                        },function(){
                            _hideTooltip();
                        });
                    }
                });

                spans.each(function(i,e){
                    e = $(e);
                    if(parseInt(e.text()) == 0)
                        e.remove();
                });

                List.redrawHeadings();
            }
        });
    };

    var _showTooltip = function(memberList,anchor) {

        DOM.tooltipList.empty();

        $.each(memberList,function(slug,member){
            DOM.tooltipList.append($('<li><span>'+member.Count+'</span>'+member.Title+'</li>'));
        });

        $('li:last',DOM.tooltipList).css('border','0');

        var pos = anchor.offset();
        DOM.tooltipPanel.css({top:pos.top+'px',left:(anchor.width()+pos.left+30)+'px'});
        DOM.tooltipPanel.stop().css({opacity:1.0}).show();
    };

    var _hideTooltip = function() {
        DOM.tooltipPanel.stop().fadeOut();
    };

    var _initEdit = function() {

        if(activeEditNode != null) {
            _removeMe(false); //may have to make this synchronous if ajax requests overlap for list expand
        }

        formChanged = false;
        loadDate = new Date();

        _addMe();
    };

    var _render = function() {
        DOM.activatePanelLink = $('<div class="active-edits-activate-link" style="display:none"><a href="#" title="Show/Hide Active Edits">Active Edits <em class="save-msg" style="display:none">Out of Date</em><em class="frm-chng" style="display:none">Unsaved Changes</em><em class="cnt">0</em></a></div>');

        $('#app-main-header > h2').before(DOM.activatePanelLink);

        DOM.editListPanel = $('<div class="active-edits-list" style="display:none"></div>');

        DOM.editList = $('<ol></ol>');

        DOM.saveMessage = $('<div class="save-msg" style="display:none"></div>');

        DOM.editListPanel.append(DOM.editList);
        DOM.editListPanel.append(DOM.saveMessage);

        $('a',DOM.activatePanelLink).click(function(event){
            event.preventDefault();
            if(DOM.activatePanelLink.hasClass('open')) {
                DOM.activatePanelLink.removeClass('open');
                DOM.editListPanel.fadeOut('fast');
            } else {
                DOM.activatePanelLink.addClass('open');

                var offset = DOM.activatePanelLink.offset();

                DOM.editListPanel.css({
                    top  :(offset.top+DOM.activatePanelLink.height()+7)+'px',
                    left :(offset.left+DOM.activatePanelLink.width()-(DOM.editListPanel.width()+20))+'px'
                });
                DOM.editListPanel.fadeIn('fast');
            }
        });

        DOM.countIndicator = $('em.cnt',DOM.activatePanelLink);
        DOM.editIndicator = $('em.frm-chng',DOM.activatePanelLink);
        DOM.saveIndicator = $('em.save-msg',DOM.activatePanelLink);

        $('body').append(DOM.editListPanel);
    };

    var _updateCount = function(count) {
        var old = DOM.countIndicator.text();
        if(old != count) {
            DOM.countIndicator.text(count);
            DOM.countIndicator.effect("highlight", { color:'#ffffff' }, 2000);
        }
    };

    var _refresh = function(callback) {

        var nq = new NodeQuery({
            'Elements.in' : '@active-edits',
            'Status.eq' : 'draft',
            'OutTags.select' : '#active-edit-member',
            'Meta.select' : '#form-changed',
            'OutTags.exist' : taggableRecord.NodeRef+'#active-edit-record',
            'Keys' : 'Cheaters,ActiveDate'
        },{
            CreationDate : 'DESC'
        },options.MaxActiveEdits,1);

        nodeService.findAll(nq,{
            params : function(params) {
                params['Heartbeat'] = activeEditNode.Slug;
            },
            success : function(nodeQuery) {

                _updateCount(nodeQuery.getTotalRecords());

                var edits = new Array();
                var anyEdits = false;

                $(nodeQuery.getResults()).each(function(i,node){
                    var memberSlug = node.Cheaters['#active-edit-member'].TagSlug;

                    var found = $.grep(edits,function(edit,i){
                        if(edit.MemberSlug == memberSlug){

                            if(node.Cheaters['#form-changed'].MetaValue == "1")
                                edit.Edits = true;

                            edit.Count++;

                            return true;
                        }

                        return false;
                    });

                    if(found.length == 0) {
                        var m = dateRegEx.exec(node.ActiveDate);
                        var nodeDate = new Date(m[1],parseInt(m[2])-1,m[3],m[4],m[5],m[6]);

                        edits.push({
                            MemberSlug : memberSlug,
                            Count : 1,
                            Edits : node.Cheaters['#form-changed'].MetaValue == "1",
                            Title : node.Cheaters['#active-edit-member'].TagLinkTitle
                        });
                    }
                });

                DOM.editList.empty();
                $(edits).each(function(i,edit){
                    DOM.editList.append($('<li><span class="num">'+edit.Count+'</span>'+(edit.Edits?'<span class="frm-chng">unsaved changes</span>':'')+edit.Title+'</li>'));
                    if(edit.Edits)
                        anyEdits = true;
                });

                if(anyEdits) {
                    if(DOM.editIndicator.css('display') == 'none')
                        DOM.editIndicator.show().effect('highlight',{color:'#ffffff'},2000);
                } else {
                    DOM.editIndicator.hide();
                }

                if(typeof callback == 'function')
                    callback();
            }
        });
    };

    var _updateFormChanged = function() {

        nodeService.updateMeta(activeEditNode,'#form-changed',"1",{
            nonce : options.UpdateMetaNonce,
            error : function() {
                formChanged = false;
            }
        });
    };

    var _addMe = function() {
        var node = new NodeObject();

        node.Element = { Slug: 'active-edit' };
        node.Title = 'Active Edit';
        //node.Slug = 'active-edit';

        nodeService.add(node,{
            nonce : options.AddNonce,
            success : function(node) {

                activeEditNode = node;

                _render();

                _refresh(function(){

                    timers.Refresh = setInterval( function(){
                        _refresh();
                    }, options.HeartbeatFrequency*1000);

                    DOM.activatePanelLink.fadeIn(1500);

                    $(window).unload(function() {
                        _removeMe();
                    });

                    $(document).bind('form_changed', function() {
                        if(!formChanged) {
                            formChanged = true;
                            _updateFormChanged();
                        }
                    });
                });
            },
            params : function(params) {
                params['RecordSlug'] = taggableRecord.Slug;
                params['RecordElementSlug'] = taggableRecord.Element.Slug;
            }
        });
    };

    var _removeMe = function(_async) {

        if(activeEditNode == null) return;

        DOM.activatePanelLink.remove();
        DOM.editListPanel.remove();

        clearInterval(timers.Refresh);

        nodeService.remove(activeEditNode,{
            nonce : options.DeleteNonce,
            async : typeof _async == 'undefined' ? false : _async
        });
        activeEditNode = null;
    };

    return {
        initList : function(_options) {
            _initList(_options);
        },

        init : function(_taggableRecord,_options) {

            taggableRecord = _taggableRecord;

            var m = dateRegEx.exec(taggableRecord.ModifiedDate);
            taggableRecord.ModifiedDate = new Date(m[1],parseInt(m[2])-1,m[3],m[4],m[5],m[6]);

            _setOptions(_options);

            taggableRecord.bind(NodeObject.EVENTS.INITIALIZED,function(){
                _initEdit();
            });
        },

        removeMe : function() {
            _removeMe();
        }
    }
}();
