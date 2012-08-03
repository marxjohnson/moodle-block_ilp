/**
 * @copyright 2012 Taunton's College UK
 * @author Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @licence GNU General Public Licence version 3
 * @package ILP
 */

// global variables
var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;

// hide the accordions from view while the page is being rendered
//Dom.addClass('content', 'hideaccordion');

function get_height(elem) {
    // work out the height of the rendered element minus the extra bits
    var padding = parseFloat(Dom.getStyle(elem, "padding-top")) + parseFloat(Dom.getStyle(elem, "padding-bottom"));
    var border = parseFloat(Dom.getStyle(elem, "border-top-width")) + parseFloat(Dom.getStyle(elem, "border-bottom-width"));
    //additional check added as IE would sometimes return isNaN
    if (isNaN(border)) border = 0;

    return elem.offsetHeight - padding - border;
}

/**
 * Animates the opening and closing of accordions.
 *
 * @param elem
 * @param from
 * @param to
 * @return
 */
function toggle_container(elem, from, to, callback) {

    // disable the onclick so it can't be pressed twice
    elem.onclick = null;

    // add the current id to the location bar
    //window.location.href = new RegExp("[^#]+").exec(window.location.href)+'#'+elem.id;;

    // get the top level div for the page
    var page = Dom.get('page');

    // get the container to animate
    var container = Dom.get(elem.id+'_container');

    if(to == 0) {
        // fix the height of the page so the animation isn't screwy
        Dom.setStyle(page, "height", get_height(page)+"px");

        // reset the desired height in case ajax has expanded the content
        from = get_height(container);

        // add the closed icon
        if (icon = document.getElementById(elem.id+'_icon')) {
            icon.setAttribute('src', M.ilp_dashboard_timeline_tab.closed_image);
        }

        // set the overflow to hidden on the container so we don't get scroll bars
        Dom.setStyle(container, "overflow", "hidden");

    } else {
        // add the open icon
        if (icon = document.getElementById(elem.id+'_icon')) {
            icon.setAttribute('src', M.ilp_dashboard_timeline_tab.open_image);
        }
    }

    // show the hidden div
    Dom.setStyle(container, "display", "block");

    // set the animation properties
    var attributes = { height: { from: from, to: to} };

    // create the animation object
    var anim = new YAHOO.util.Anim(elem.id+'_container', attributes, Math.abs(from-to)/1000);

    if (callback) {
        anim.onComplete.subscribe(callback);
    } else {
        // set the oncomplete callback
        anim.onComplete.subscribe(function() {
            // restore the onclick
            elem.onclick = function() { toggle_container(this, to, from); };

            if(to == 0) {
                // hide the container
                Dom.setStyle(container, "display", "none");

                // allow the page size to drop back now the animation is complete
                Dom.setStyle(page, "height", "auto");

            } else {
                // set the height to auto so it can grow with new ajax content
                Dom.setStyle(container, "height", "auto");

                // set the overflow to auto so we can see any expanded content
                Dom.setStyle(container, "overflow", "auto");
            }

        });
    }

    // do it
    anim.animate();
}


/**
 * Initialisation function that sets up the javascript for the page.
 */
M.ilp_dashboard_timeline_tab = {
    // params from PHP
    open_image : null,
    closed_image : null,
    userid: null,
    selectedtab: null,
    tabitem: null,
    Y: null,

    init : function(Y, open_image, closed_image, userid, selectedtab, tabitem) {

        this.Y = Y;
        this.open_image = open_image;
        this.closed_image = closed_image;
        this.userid = userid;

        this.selectedtab = selectedtab;
        this.tabitem = tabitem;

        var heights = new Array();

        // get all the accordion headers
        var headers = Dom.getElementsByClassName('commentheading', 'h3');

        // get the currently selected accordion
        var current = new RegExp("#(.+)").exec(window.location.href);

        var showall = Y.one('.showall a');

        for(i=0; i<headers.length; i++) {

            //cjheck if the _selector div exists if it doesn't there are no comments and thus no need for the
            //onclick
            if (document.getElementById(headers[i].id+'_container') != null) {
                // get the height of the container element
                heights[headers[i].id] = get_height(Dom.get(headers[i].id+'_container'));

                Dom.setStyle(Dom.get(headers[i].id+'_container'),"hiddencontainer");

                // set the cursor style so the user can see this is clickable
                Dom.setStyle(headers[i], "cursor", "pointer");

                // create the img icon and insert it into the start of the header
                img = document.createElement('img');
                img.setAttribute('id', headers[i].id+'_icon');
                img.setAttribute('class', 'collapse');

                headers[i].insertBefore(img, document.getElementById(headers[i].id).firstChild);

                // check if this container should be closed
                if(!current || current[1] != headers[i].id) {

                    // set the onclick to open the container
                    headers[i].onclick = function() { toggle_container(this, 0, heights[this.id]); };

                    // close and hide the container
                    Dom.setStyle(Dom.get(headers[i].id+'_container'), "display", "none");
                    Dom.setStyle(Dom.get(headers[i].id+'_container'), "overflow", "hidden");

                    // add the closed icon
                    document.getElementById(headers[i].id+'_icon').setAttribute('src', closed_image);
                } else {
                    // set the onclick to close the container
                    headers[i].onclick = function() { toggle_container(this, heights[this.id], 0); };

                    // add the open icon
                    document.getElementById(headers[i].id+'_icon').setAttribute('src', open_image);
                }
            }
        }

        // allow the accordions to be seen now that rendering is complete
        Dom.removeClass('content', 'hideaccordion');

        select = document.getElementById('reportstateselect');
        if (select) {
            //add the onchange event to the select button
            select.addEventListener(
                 'change',
                 function() {},
                 false
              );
        }

        Y.all('.delete_reportentry').on('click', this.delete_reportentry, this);
        Y.all('.display_commentform').on('click', this.display_commentform, this);
        Y.all('.delete_reportcomment').on('click', this.delete_reportcomment, this);
        Y.all('.addbuttons .add a').on('click', this.edit_reportentry, this);
        Y.all('.showprivate').on('click', this.show_private, this);

        if (showall) {
            showall.on('click', this.show_all);
        }
    },

    refresh_tab: function(html) {
        Y = this.Y;
        var container = Y.one('#ilp_dashboard_timeline_tab_wrapper');
        var newcontainer = Y.Node.create(html);
        container.replace(newcontainer);
        this.init(Y, this.open_image, this.closed_image, this.userid, this.selectedtab, this.tabitem);
    },

    delete_reportentry: function(e) {
        Y = this.Y;
        e.preventDefault();

        var conf = confirm("Are you sure you want to delete this entry? This action cannot be undone.");

        if (conf) {
            url = e.target.get('href').split('.php').join('_ajax.php');
            Y.io(url, {
                on: {
                    success: function(id, o) {
                        var response = Y.JSON.parse(o.responseText);
                        this.refresh_tab(response.output);
                    },
                    failure: function(id, o) {
                        var response = Y.JSON.parse(o.responseText);
                        alert(response.output);
                    }
                },
                context: this
            });
        }
    },

    display_commentform: function(e) {
        var Y = this.Y
        e.preventDefault();

        var url = e.target.get('href');
        var form = Y.Node.create('<form method="post" action="'+url+'" class="edit_entrycomment_form">'
            +'<label for="id_value" style="display:block;">Comment:</label>'
            +'<textarea id="id_value" name="value" cols="" rows=""></textarea>'
            +'<div><button class="edit_entrycomment">Submit</button><button class="remove_commentform">Cancel</button>'
            +'<img src="'+M.cfg.loadingicon+'" alt="Loading..." style="visibility:hidden" /></div>');
        var container = e.target.get('parentNode').get('parentNode').get('parentNode').get('parentNode');
        var id = e.target.get('parentNode').get('parentNode').one('.commentheading').get('id')+'_comment_form';
        form.set('id', id);
        container.appendChild(form);

        form.one('.remove_commentform').on('click', this.remove_commentform);
        form.one('.edit_entrycomment').on('click', this.edit_entrycomment, this);

        // If the form is currently off screen (e.g. there already lots of comments), jump to it
        if (!form.inViewportRegion()) {
            window.location.hash = id;
        }
    },

    remove_commentform: function(e) {
        e.preventDefault();
        e.target.get('parentNode').get('parentNode').remove(true);
    },

    edit_entrycomment: function(e) {
        var Y = this.Y;
        e.preventDefault();
        var form = e.target.get('parentNode').get('parentNode');
        form.one('img').setStyle('visibility', 'visible');
        // The real form uses an HTML editor, so lets HTMLify the submitted text
        var value = encodeURI('<p>'+form.one('#id_value').get('value').replace("\n", "<br />")+'</p>');
        var urlparts = form.get('action').split('?');
        var url = urlparts[0].split('.php').join('_ajax.php');
        var data = urlparts[1]+'&sesskey='+M.cfg.sesskey+'&value='+value+'&creator_id='+this.userid;
        data += '&submitbutton=Submit&_qf__edit_entrycomment_mform=1';
        Y.io(url, {
            method: 'post',
            data: data,
            on: {
                success: function(id, o) {
                    var response = Y.JSON.parse(o.responseText);
                    this.refresh_tab(response.output);
                },
                failure: function(id, o) {
                    var response = Y.JSON.parse(o.responseText);
                    alert(response.output);
                }
            },
            context: this
        });
    },

    delete_reportcomment: function(e) {
        Y = this.Y;
        e.preventDefault();

        var conf = confirm("Are you sure you want to delete this comment? This action cannot be undone.");

        if (conf) {
            url = e.target.get('href').split('.php').join('_ajax.php');
            Y.io(url, {
                on: {
                    success: function(id, o) {
                        var response = Y.JSON.parse(o.responseText);
                        this.refresh_tab(response.output);
                    },
                    failure: function(id, o) {
                        var response = Y.JSON.parse(o.responseText);
                        alert(reponse.output);
                    }
                },
                context: this
            });
        }
    },

    edit_reportentry: function(e) {
        var Y = this.Y;
        e.preventDefault();
        if (Y.one('#edit_reportentry_form_container').getStyle('visibilty') != 'hidden') {
            this.clear_reportentry();
        }

        if (e.target.get('id') == 'id_submitbutton') {
            var form = document.getElementById('mform1');
            Y.one('#id_cancel').set('disabled', 'disabled');
            var url = Y.one('#mform1').get('action').split('.php').join('_ajax.php')+'?';
            url += 'selectedtab='+this.selectedtab+'&tabitem='+this.tabitem;
            var method = 'post';
        } else {
            var form = null
            var url = e.target.get('href').split('.php').join('_ajax.php')+'&';
            var method = 'get';
        }

        Y.one('.addbuttons .loading').setStyle('visibility', 'visible');
        Y.io(url, {
            method: method,
            on: {
                success: function(id, o) {
                    var response = Y.JSON.parse(o.responseText);
                    if (response.state == 0) {
                        // Build the form from the mform's HTML
                        var form = Y.Node.create(response.output);
                        Y.one('#edit_reportentry_form_container').setContent(form);
                        form.one('#id_cancel').on('click', this.clear_reportentry, this);
                        form.one('#id_submitbutton').on('click', this.edit_reportentry, this);
                        // Expand the container to reveal the form
                        toggle_container(document.getElementById('edit_reportentry_form'), 0, 500);
                        // Load any Javascript required by the form
                        if (response.script.length > 0) {
                            if (script = Y.one('#ilp_dynamic_script')) {
                                script.remove();
                            }
                            el = document.createElement('script');
                            el.id = 'ilp_dynamic_script';
                            el.textContent = response.script;
                            document.body.appendChild(el);
                        }
                    } else {
                        this.refresh_tab(response.output);
                    }
                    Y.one('.addbuttons .loading').setStyle('visibility', 'hidden');
                },
                failure: function(id, o) {
                    var response = Y.JSON.parse(o.responseText);
                    if (o.status == 302 && response.output.search(M.cfg.wwwroot) > -1) {
                        window.location = response.output;
                    } else {
                        alert(response.output);
                    }
                    Y.one('.addbuttons .loading').setStyle('visibility', 'hidden');
                }
            },
            form: form,
            context: this
        });
    },

    clear_reportentry: function(e) {
        var Y = this.Y;
        if (e) {
            e.preventDefault();
        }
        container = document.getElementById('edit_reportentry_form');
        toggle_container(container, get_height(container), 0, function() {
            Y.one('#edit_reportentry_form_container form').remove(true);
        });
    },

    show_all: function(e) {
        Y = this.Y;
        e.preventDefault();
        Y.one('.showall .loading').setStyle('visibility', 'visible');
        url = e.target.get('href').replace('view_main', 'refresh_tab_ajax');
        Y.io(url, {
            on: {
                success: function(id, o) {
                    var response = Y.JSON.parse(o.responseText);
                    this.refresh_tab(response.output);
                },
                failure: function(id, o) {
                    var response = Y.JSON.parse(o.responseText)
                    alert(response.output);
                },
            },
            context: this
        });
    },

    show_private: function(e) {
        Y = this.Y;
        e.preventDefault();
        e.target.get('parentNode').get('parentNode').one('.private').setStyle('display', 'block');
        e.target.set('innerHTML', 'Hide');
        e.target.detach('click');
        e.target.on('click', this.hide_private, this);
    },

    hide_private: function(e) {
        Y = this.Y;
        e.preventDefault();
        e.target.get('parentNode').get('parentNode').one('.private').setStyle('display', 'none');
        e.target.set('innerHTML', 'Display');
        e.target.detach('click');
        e.target.on('click', this.show_private, this);
    }
}

