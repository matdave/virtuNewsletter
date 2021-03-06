VirtuNewsletter.grid.Newsletters = function(config) {
    config = config || {};

    Ext.applyIf(config, {
        id: 'virtunewsletter-grid-newsletters',
        url: VirtuNewsletter.config.connectorUrl,
        baseParams: {
            action: 'mgr/newsletters/getList',
            parentId: config.record && config.record.id ? config.record.id : 0
        },
        autoHeight: true,
        fields: ['id', 'subject', 'scheduled_for', 'subscribers', 'queue', 'is_recurring', 'is_active'],
        paging: true,
        remoteSort: true,
        preventRender: true,
        margins: 15,
        autoExpandColumn: 'subject',
        dateFormat: config.dateFormat || 'U',
        displayFormat: config.displayFormat || 'Y-m-d',
        columns: [
            {
                header: _('id'),
                dataIndex: 'id',
                width: 40,
                sortable: true,
                hidden: true
            }, {
                header: _('virtunewsletter.subject'),
                dataIndex: 'subject',
                sortable: true
            }, {
                header: _('virtunewsletter.scheduled_for'),
                dataIndex: 'scheduled_for',
                sortable: true,
                width: 200,
                fixed: true,
                renderer: function(value) {
                    if (value !== '') {
                        var date = Date.parseDate(value, config.dateFormat);
                        return date.format(config.displayFormat);
                    }
                }
            }, {
                header: _('virtunewsletter.subscribers'),
                dataIndex: 'subscribers',
                sortable: true,
                width: 100,
                fixed: true
            }, {
                header: _('virtunewsletter.queue'),
                dataIndex: 'queue',
                sortable: true,
                width: 100,
                fixed: true
            }, {
                xtype: 'checkcolumn',
                header: _('virtunewsletter.recurring'),
                dataIndex: 'is_recurring',
                sortable: false,
                width: 100,
                fixed: true,
                processEvent: Ext.emptyFn() // don't process recurrence in grid!
            }, {
                xtype: 'checkcolumn',
                header: _('virtunewsletter.active'),
                dataIndex: 'is_active',
                sortable: false,
                width: 70,
                fixed: true,
                processEvent: this.processMouseEvent
            }, {
                header: _('actions'),
                xtype: 'actioncolumn',
                dataIndex: 'id',
                width: 80,
                fixed: true,
                sortable: false,
                items: [
                    {
                        iconCls: 'virtunewsletter-icon-delete virtunewsletter-icon-actioncolumn-img',
                        tooltip: _('virtunewsletter.remove'),
                        altText: _('virtunewsletter.remove'),
                        handler: function(grid, row, col) {
                            var rec = this.store.getAt(row);
                            this.removeNewsletter(rec.data.id);
                        },
                        scope: this
                    }, {
                        iconCls: 'virtunewsletter-icon-magnifier virtunewsletter-icon-actioncolumn-img',
                        tooltip: _('virtunewsletter.detail'),
                        altText: _('virtunewsletter.detail'),
                        handler: function(grid, row, col) {
                            var rec = this.store.getAt(row);
                            this.loadNewsletter(rec.data.id);
                        },
                        scope: this
                    }
                ]
            }
        ],
        tbar: [
            {
                text: _('virtunewsletter.add_new_schedule'),
                handler:  function() {
                    this.newsletterPanel();
                },
                scope: this
            }
        ]
    });

    VirtuNewsletter.grid.Newsletters.superclass.constructor.call(this, config);

    this.getStore().on('load', function() {
        Ext.getCmp('virtunewsletter-newsletters-tabs').doLayout();
    }, this);
};
Ext.extend(VirtuNewsletter.grid.Newsletters, MODx.grid.Grid, {
    getMenu: function(node, e) {
        var menu = [
            {
                text: _('virtunewsletter.remove'),
                handler: function(btn, e) {
                        this.removeNewsletter(this.menu.record.id);
                }
            }
        ];

        return menu;
    },
    removeNewsletter: function(id) {
        MODx.msg.confirm({
            title: _('virtunewsletter.remove'),
            text: _('virtunewsletter.remove_confirm'),
            url: VirtuNewsletter.config.connectorUrl,
            params: {
                action: 'mgr/newsletters/remove',
                id: id
            },
            listeners: {
                'success': {
                    fn: this.refresh,
                    scope: this
                }
            }
        });
    },
    loadNewsletter: function(id) {
        if (!this.pageMask) {
            this.pageMask = new Ext.LoadMask(Ext.getBody(), {
                msg: _('virtunewsletter.please_wait')
            });
        }
        this.pageMask.show();

        MODx.Ajax.request({
            url: VirtuNewsletter.config.connectorUrl,
            params: {
                action: 'mgr/newsletters/get',
                id: id
            },
            listeners: {
                'success': {
                    fn: function(res) {
                        if (res.success === true) {
                            this.newsletterPanel(res.object);
                        }
                        return this.pageMask.hide();
                    },
                    scope: this
                },
                'failure': {
                    fn: function() {
                        return this.pageMask.hide();
                    },
                    scope: this
                }
            }
        });
    },
    newsletterPanel: function(record) {
        record = record|| {};
        var tabs = Ext.getCmp('virtunewsletter-newsletters-tabs');
        if (typeof(tabs) === 'undefined') {
            return false;
        }
        var newTab = MODx.load({
            title: record.id ? _('virtunewsletter.schedule_update') : _('virtunewsletter.schedule_create'),
            closable: true,
            xtype: 'virtunewsletter-panel-newsletter-content',
            id: 'virtunewsletter-panel-newsletter-content-tab-' + (record.id ? record.id : 'new'),
            record: record
        });
        tabs.add(newTab);
        tabs.setActiveTab(newTab);
    },
    processMouseEvent: function (name, e, grid, rowIndex, colIndex) {
        if (name === 'mousedown') {
            var record = grid.store.getAt(rowIndex);
            record.set(this.dataIndex, !record.data[this.dataIndex]);
            MODx.Ajax.request({
                url: VirtuNewsletter.config.connectorUrl,
                params: {
                    action: 'mgr/newsletters/updateFromGrid',
                    data: JSON.stringify(record.data)
                },
                listeners: {
                    'success': {
                        fn: function () {
                            grid.refresh();
                        }
                    },
                    'failure': {
                        fn: function (r) {
                            grid.refresh();
                        }
                    }
                }
            });
            return false;
        } else {
            return Ext.grid.ActionColumn.superclass.processEvent.apply(this, arguments);
        }
    }
});
Ext.reg('virtunewsletter-grid-newsletters', VirtuNewsletter.grid.Newsletters);