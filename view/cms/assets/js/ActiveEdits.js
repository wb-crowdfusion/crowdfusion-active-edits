var ActiveEdits = function() {

  var DOM = {};
  var options = {};
  var timers = {};
  var taggableRecord = null;
  var formChanged = false;
  var loadDate = null;
  var listTitleIndex = 1;
  var dateRegEx = /(\d\d\d\d)-(\d\d)-(\d\d)T(\d\d):(\d\d):(\d\d)([+-])(\d\d):(\d\d)/;

  var _setOptions = function(_options) {
    options = $.extend({
      AddNonce: null,
      DeleteNonce: null,
      UpdateMetaNonce: null,
      ServerDate: null,
      HeartbeatFrequency: 60,
      ListCheckFrequency: 30,
      CurrentUser: {
        Slug: null
      },
      PopupLoaded: false
    }, _options || {});
  };

  var _initList = function(_options) {
    _setOptions(_options);

    var $body = $('body');

    if (!$body.hasClass('list') || $body.hasClass('signin')) return;

    if (!SystemService.aspectsShareElements(List.getAspect(), 'mixin-track-active-edits')) return;

    $('#app-content table.data tr:eq(0) th').each(function(i, e) {
      e = $(e);
      if (e.text().toUpperCase() == 'TITLE')
        listTitleIndex = i;
    });

    DOM.tooltipPanel = $('<div id="active-edit-tooltip" style="display:none"></div>');
    DOM.tooltipList = $('<ul></ul>');

    DOM.tooltipPanel.append(DOM.tooltipList);

    $('body').append(DOM.tooltipPanel);

    setTimeout(function() {
      _refreshList();
      setInterval(_refreshList, options.ListCheckFrequency * 1000);
    }, 1000);
  };

  var _refreshList = function() {
    var slugs = [];

    $('#app-content table.data tr:not(:first-child)').each(function() {
      var slug = $(this).attr('id');
      if (slug.substring(0, 9) != 'expanded_') {
        if (slug.substring(0, 10) == 'collapsed_') {
          slug = slug.substring(10);
        }
        slugs.push(slug);
      }
    });

    $.post('/active-edits/total-members', {slugs: slugs}, function(json) {

      // remove all counts
      var spans = $('#app-content table.data tr td span.active-edit-count');
      spans.each(function() {
        $(this).remove();
      });

      List.redrawHeadings();

      $.each(json, function(slug, members) {
        if (members.length === 0) return;

        var tr = $('#app-content table.data tr:[id=' + slug + ']');
        tr = tr.length ? tr : $('#app-content table.data tr:[id=collapsed_' + slug + ']');

        var td = tr.children('td:eq(' + listTitleIndex + ')'),
          span = $('span.active-edit-count', td);

        if (span.length === 0) {
          span = $('<span class="active-edit-count" style="display:none">' + members.length + '</span>');
          td.prepend(span);
          span.fadeIn(2000);
        } else {
          span.text(parseInt(span.text()) + 1);
        }

        $.each(members, function(i, member) {
          members[i] = { Title: member.user_name, Count: 1 }; // count always going to be 1
        });

        span.data('memberList', members);

        span.unbind().hover(function() {
          _showTooltip(span.data('memberList'), span);
        }, function() {
          _hideTooltip();
        });
      });
    }, 'json');
  };

  var _showTooltip = function(memberList, anchor) {
    DOM.tooltipList.empty();

    $.each(memberList, function(slug, member) {
      DOM.tooltipList.append($('<li><span>' + member.Count + '</span>' + member.Title + '</li>'));
    });

    $('li:last', DOM.tooltipList).css('border', '0');

    var pos = anchor.offset();
    DOM.tooltipPanel.css({
      top: pos.top + 'px',
      left: (anchor.width() + pos.left + 30) + 'px'
    });
    DOM.tooltipPanel.stop().css({
      opacity: 1.0
    }).show();
  };

  var _hideTooltip = function() {
    DOM.tooltipPanel.stop().fadeOut();
  };

  var _initEdit = function() {
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

    $('a', DOM.activatePanelLink).click(function(event) {
      event.preventDefault();
      if (DOM.activatePanelLink.hasClass('open')) {
        DOM.activatePanelLink.removeClass('open');
        DOM.editListPanel.fadeOut('fast');
      } else {
        DOM.activatePanelLink.addClass('open');

        var offset = DOM.activatePanelLink.offset();

        DOM.editListPanel.css({
          top: (offset.top + DOM.activatePanelLink.height() + 7) + 'px',
          left: (offset.left + DOM.activatePanelLink.width() - (DOM.editListPanel.width() + 20)) + 'px'
        });
        DOM.editListPanel.fadeIn('fast');
      }
    });

    DOM.countIndicator = $('em.cnt', DOM.activatePanelLink);
    DOM.editIndicator = $('em.frm-chng', DOM.activatePanelLink);
    DOM.saveIndicator = $('em.save-msg', DOM.activatePanelLink);

    $('body').append(DOM.editListPanel);
  };

  var _updateCount = function(count) {
    var old = DOM.countIndicator.text();
    if (old != count) {
      DOM.countIndicator.text(count);
      DOM.countIndicator.effect("highlight", {
        color: '#ffffff'
      }, 2000);
    }
  };

  var _refresh = function(callback) {
    $.post('/active-edits/' + taggableRecord.Slug, function(members) {
      _updateCount(Object.keys(members).length);

      var edits = [], anyEdits = false;

      $.each(members, function(i, member) {
        var found = $.grep(edits, function(edit) {
          if (edit.Slug == member.user_slug) {

            if (member.meta_updated) {
              edit.Edits = true;
            }

            edit.Count++;

            return true;
          }

          return false;
        });

        if (found.length === 0) {
          edits.push({
            Count: 1,
            Slug: member.user_slug,
            Title: member.user_name,
            Edits: member.meta_updated
          });
        }
      });

      DOM.editList.empty();
      $(edits).each(function(i, edit) {
        DOM.editList.append($('<li><span class="num">' + edit.Count + '</span>' + (edit.Edits ? '<span class="frm-chng">unsaved changes</span>' : '') + edit.Title + '</li>'));
        if (edit.Edits) {
          anyEdits = true;
        }
      });

      if (anyEdits) {
        if (DOM.editIndicator.css('display') == 'none')
          DOM.editIndicator.show().effect('highlight', {
            color: '#ffffff'
          }, 2000);
      } else {
        DOM.editIndicator.hide();
      }

      if (typeof callback == 'function') {
        callback();
      }

      warningPopup().execute(edits);
    }, 'json');
  };

  var _updateFormChanged = function() {
    $.ajax({
      type: 'POST',
      dataType: 'json',
      async: false,
      url: '/active-edits/' + taggableRecord.Slug + '/update-meta',
      complete: function(response) {
        _refresh();

        if (response == 'error') {
          formChanged = false;
        }
      }
    });
  };

  var _addMe = function() {
    _render();

    _refresh(function() {
      timers.Refresh = setInterval(function() {
        _refresh();
      }, options.HeartbeatFrequency * 1000);

      DOM.activatePanelLink.fadeIn(1500);

      window.addEventListener('beforeunload', function() {
        _removeMe();
      });
      window.addEventListener('unload', function() {
        _removeMe();

        if (DOM.activatePanelLink) {
          DOM.activatePanelLink.remove();
        }
        if (DOM.editListPanel) {
          DOM.editListPanel.remove();
        }

        clearInterval(timers.Refresh);
      });

      $(document).bind('form_changed', function() {
        if (!formChanged) {
          formChanged = true;
          _updateFormChanged();
        }
      });
    });
  };

  var _removeMe = function() {
    formChanged = false;

    $.ajax({
      type: 'POST',
      dataType: 'json',
      async: false,
      url: '/active-edits/' + taggableRecord.Slug + '/remove-member'
    });
  };

  var _slugify = function(slug) {
    return slug.toLowerCase()
        .replace(/\s+/g, '-')      // Replace spaces with -
        .replace(/[^\w\-]+/g, '-') // Remove all non-word chars
        .replace(/\-\-+/g, '-')    // Replace multiple - with single -
        .replace(/^-+/, '')        // Trim - from start of text
        .replace(/-+$/, '');       // Trim - from end of text
  };

  /**
   * Contains functionality for warning popup addition.
   *
   * @depended on http://www.ericmmartin.com/projects/simplemodal/
   */
  var warningPopup = function warningPopup() {
    var objActiveEditors = null;

    var showModal = function(html) {
      var myOptions = {
        focus: true,
        opacity: 80,
        minHeight: 350,
        minWidth: 650,
        dataId: 'warningModal',
        overlayClose: false,
        onShow: function(dialog) {
          dialog.container.addClass('warning');
          modalAttachments();
        },
        onOpen: function(dialog) {
          dialog.overlay.fadeIn(100, function() {
            dialog.container.slideDown('fast', function() {
              // dialog.data.fadeIn('fast');
              dialog.data.show();
            });
          });
        }
      };

      $.modal(html, myOptions);
    };

    /**
     * Routine Attached to modal when it loads.
     *
     * Note: using LIVE instead of on since old version of jquery is installed in cms
     */
    var modalAttachments = function() {
      $('.btn-ae-continue').live('click', function() {
        $.modal.close();
      });

      $('.btn-ae-cancel').live('click', function() {
        history.go(-1);
      });
    };

    var execute = function(activeEditors) {
      if (activeEditors) {
        objActiveEditors = activeEditors;
      }
      if (options.PopupLoaded) {
        return;
      }

      // number of unique users
      var numUsers = activeEditors.length;

      // only a single user (yourself)
      if (numUsers < 2) {
        // ignore popup for first user
        options.PopupLoaded = true;

        return;
      }

      // name of the first user is listed last in the object
      var name;
      for (var i in activeEditors) {
        var user = activeEditors[i];

        if (!name && options.CurrentUser.Slug !== user.Slug) {
          name = user.Title;
        }
      }

      var strWarnMessage = '<span class="activeUserName">' + name + '</span> is editing this post';
      var strCancelMessage = 'Cancel and ask ' + name + ' to get out of the post';

      if (numUsers > 2) {
        strWarnMessage = 'There are multiple users editing this post';
        strCancelMessage = 'Cancel and ask the active users to get out of the post';
      }

      var html = '<div>' +
        '<h1 class="title">STOP</h1>' +
        '<div class="message">' + strWarnMessage + '</div>' +
        '<div class="btn-container">' +
          '<button type="button" class="btn-ae-continue">Enter at your own risk</button>' +
          '<button type="button" class="btn-ae-cancel">' + strCancelMessage + '</button>' +
        '</div>' +
      '</div>';

      showModal(html);

      options.PopupLoaded = true;
    };

    return {
      showModal: showModal,
      activeEditors: objActiveEditors,
      execute: execute
    };
  };

  return {
    initList: function(_options) {
      _initList(_options);
    },

    init: function(_taggableRecord, _options) {
      taggableRecord = _taggableRecord;

      taggableRecord.Slug = _slugify(taggableRecord.Element.Slug) + '-' + _slugify(taggableRecord.Slug);

      var m = dateRegEx.exec(taggableRecord.ModifiedDate);
      taggableRecord.ModifiedDate = new Date(m[1], parseInt(m[2]) - 1, m[3], m[4], m[5], m[6]);

      _setOptions(_options);

      taggableRecord.bind(NodeObject.EVENTS.INITIALIZED, function() {
        _initEdit();
      });
    },

    removeMe: function() {
      _removeMe();
    },

    popupMethods: warningPopup()
  };
}();
