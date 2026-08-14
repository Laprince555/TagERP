import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'TagERP Documentation',
  description: 'Technical Documentation for TagERP Engines',
  lang: 'ar',
  base: '/docs/',
  outDir: '../public/docs',
  cleanUrls: true,

  themeConfig: {
    nav: [
      { text: 'الرئيسية', link: '/' },
      { text: 'Dynamic Table', link: '/dynamic-table/README' },
      { text: 'Dynamic Record View', link: '/dynamic-record-view/README' },
      { text: 'Record References', link: '/record-references/README' },
      { text: 'Dynamic Form', link: '/dynamic-form/record-references' }
    ],

    sidebar: {
      '/dynamic-table/': [
        {
          text: 'Dynamic Table Engine',
          items: [
            { text: 'Overview', link: '/dynamic-table/README' },
            { text: 'Quick Start', link: '/dynamic-table/quick-start' },
            { text: 'Columns', link: '/dynamic-table/columns' },
            { text: 'Visibility & Authorization', link: '/dynamic-table/visibility-authorization' },
            { text: 'Search', link: '/dynamic-table/search' },
            { text: 'Filters', link: '/dynamic-table/filters' },
            { text: 'Relationships', link: '/dynamic-table/relationships' },
            { text: 'Sorting & Pagination', link: '/dynamic-table/sorting-pagination' },
            { text: 'Preferences', link: '/dynamic-table/preferences' },
            { text: 'Saved Views', link: '/dynamic-table/saved-views' },
            { text: 'Performance', link: '/dynamic-table/performance' },
            { text: 'Security', link: '/dynamic-table/security' },
            { text: 'Testing', link: '/dynamic-table/testing' },
            { text: 'Extending', link: '/dynamic-table/extending' },
            { text: 'Troubleshooting', link: '/dynamic-table/troubleshooting' },
            { text: 'Package Extraction', link: '/dynamic-table/package-extraction' },
            { text: 'Record References', link: '/dynamic-table/record-references' }
          ]
        }
      ],
      '/dynamic-record-view/': [
        {
          text: 'Dynamic Record View Engine',
          items: [
            { text: 'Overview', link: '/dynamic-record-view/README' },
            { text: 'Quick Start', link: '/dynamic-record-view/quick-start' },
            { text: 'Architecture', link: '/dynamic-record-view/architecture' },
            { text: 'Defining Record Views', link: '/dynamic-record-view/defining-record-views' },
            { text: 'Sections & Tabs', link: '/dynamic-record-view/sections-tabs' },
            { text: 'Content Blocks', link: '/dynamic-record-view/content-blocks' },
            { text: 'Fields', link: '/dynamic-record-view/fields' },
            { text: 'Record Resolution', link: '/dynamic-record-view/record-resolution' },
            { text: 'Embedded Tables', link: '/dynamic-record-view/embedded-tables' },
            { text: 'Relations', link: '/dynamic-record-view/relations' },
            { text: 'Sub Applications', link: '/dynamic-record-view/sub-applications' },
            { text: 'Relationship Actions', link: '/dynamic-record-view/relationship-actions' },
            { text: 'Relation Picker', link: '/dynamic-record-view/relation-picker' },
            { text: 'State Isolation', link: '/dynamic-record-view/state-isolation' },
            { text: 'Security', link: '/dynamic-record-view/security' },
            { text: 'Performance', link: '/dynamic-record-view/performance' },
            { text: 'Authorization', link: '/dynamic-record-view/authorization' },
            { text: 'Request Lifecycle', link: '/dynamic-record-view/request-lifecycle' },
            { text: 'Accessibility', link: '/dynamic-record-view/accessibility' },
            { text: 'Extending', link: '/dynamic-record-view/extending' },
            { text: 'Troubleshooting', link: '/dynamic-record-view/troubleshooting' },
            { text: 'Package Extraction', link: '/dynamic-record-view/package-extraction' },
            { text: 'Testing', link: '/dynamic-record-view/testing' }
          ]
        }
      ],
      '/record-references/': [
        {
          text: 'Record References',
          items: [
            { text: 'Overview & Guide', link: '/record-references/README' }
          ]
        }
      ],
      '/dynamic-form/': [
        {
          text: 'Dynamic Form (Planned)',
          items: [
            { text: 'Record References Contract', link: '/dynamic-form/record-references' }
          ]
        }
      ]
    },

    search: {
      provider: 'local'
    }
  }
})
