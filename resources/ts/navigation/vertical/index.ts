import appsAndPages from './apps-and-pages'
import charts from './charts'
import dashboard from './dashboard'
import forms from './forms'
import others from './others'
import uiElements from './ui-elements'
import type { VerticalNavItems } from '@layouts/types'
import { applyNavPermissions } from '@/modules/acl/nav-permissions'

const items = [...dashboard, ...appsAndPages, ...uiElements, ...forms, ...charts, ...others] as VerticalNavItems

export default applyNavPermissions(items)
