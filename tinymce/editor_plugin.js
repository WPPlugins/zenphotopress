(function () {
    tinymce.PluginManager.requireLangPack('zenphotopress');
    tinymce.create('tinymce.plugins.zenphotopress', {
        getInfo: function () {
            return {
                longname: 'Zenphoto Gallery Plugin for WordPress',
                author: 'Alessandro Morandi',
                authorurl: 'http://www.simbul.net',
                infourl: 'http://www.simbul.net/zenphotopress',
                version: "1.8"
            }
        }, init: function (ed, url) {
            ed.addCommand('mceZenphotoPress', function () {
                ed.windowManager.open({file: url + '/zp_popup.php?tinyMCE=1', width: 480, height: 480, inline: 1})
            });
            ed.addButton('zenphotopress', {
                title: 'Insert Zenphoto images or galleries',
                cmd: 'mceZenphotoPress',
                image: url + '/img/zenphotopress.gif'
            })
        }, createControl: function (n, cm) {
            return null
        }
    });
    tinymce.PluginManager.add('zenphotopress', tinymce.plugins.zenphotopress)
})();