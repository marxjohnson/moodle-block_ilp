M.ilp_element_plugin_contact_list = {
    Y: '',
    init: function(Y) {
        this.Y = Y;
        Y.one('#id_stakeholdersearch').on('keyup', function(e) {
            M.ilp_element_plugin_contact_list.stakeholder_search(e.target.get('value'));
        });
    },

    stakeholder_search: function(search) {
        Y = this.Y;
        if (search.length == 0) {
            Y.one('#stakeholderresults').get('firstChild').replace(Y.Node.create('<ul />'));
        } else {
            Y.io(M.cfg.wwwroot+'/blocks/ilp/utilities/ilp_element_plugin_contact_list_stakeholders.php', {
                data: 'search='+search,
                on: {
                    success: function(id, o) {
                        var div = Y.one('#stakeholderresults');
                        var response = Y.JSON.parse(o.responseText);
                        var list = Y.Node.create('<ul />');
                        Y.Object.each(response.stakeholders, function(name, id) {
                            var item = Y.Node.create('<li><a style="cursor:pointer" class="stakeholder" name="'+id+'">'+name+'</a></li>');
                            list.appendChild(item);
                            item.on('click', function(e) {
                                M.ilp_element_plugin_contact_list.stakeholder_select(e.target.get('name'), e.target.get('innerHTML'));
                            });
                        });
                        div.get('firstChild').replace(list);
                    }
                }
            });
        }
    },

    stakeholder_select: function(id, name) {
        var div = Y.one('#extrastakeholders');
        var checkbox = Y.Node.create('<input id="extracheckbox'+id+'" name="teachers['+id+']" class="'+id+'" type="checkbox" value="1" checked="checked" />');
        var label = Y.Node.create('<label for="extracheckbox'+id+'">'+name+'</label>');
        div.appendChild(checkbox);
        div.appendChild(label);
    }
}
