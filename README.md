# Festoon

**A holiday theme for Flarum 2 that changes by itself.** Festoon dresses your
forum for the time of year: holiday colors across the whole forum, a moving
garland along the header, hats on everyone's picture beside their posts,
ornaments on widgets and posts, falling snow or leaves, and real fireworks on
New Year and the Fourth of July. When the holiday is over it moves on to the
next one, or to the season, without anyone touching a setting.

![Christmas on a discussion page: twinkling lights, Santa hats, holly and snow](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/christmas-discussion.png)

Free and MIT licensed. Built on the default Flarum theme, and it works with
Bespoke and FoF Widgets too.

---

## What's included

**US holidays**, on by default: New Year, Valentine's Day, St. Patrick's Day,
Easter, Fourth of July, Halloween, Thanksgiving and Christmas.

**Seasons** fill the time in between: spring, summer, fall and winter.

**Around the world**, off until you turn them on: Lunar New Year, Diwali,
Hanukkah, and Ramadan through Eid al-Fitr.

**Your own holidays**: add a founders' day, a forum birthday or a local
festival with its own dates, colors and decorations.

### Dates that are worked out, not typed in

Easter, Thanksgiving and the holidays from other calendars move every year.
Festoon calculates them; nothing is tied to a particular year.

| Holiday | How the date is found |
| --- | --- |
| Easter | The Gregorian Easter algorithm |
| Thanksgiving | The fourth Thursday of November |
| Lunar New Year | ICU's Chinese calendar for the month, then the exact new moon in China time for the day |
| Hanukkah | 25 Kislev in the Hebrew calendar |
| Ramadan and Eid al-Fitr | The Umm al-Qura Islamic calendar |
| Diwali | The new moon that ends Ashvin, from the positions of the sun and moon |

These are checked in the tests against published dates, including the
awkward years: 2020's extra lunar month before Diwali, 2024's Diwali that fell
across two evenings, and 2027's Lunar New Year, whose new moon is four minutes
before midnight in China. Ramadan can begin a day earlier or later where it is
set by sighting the moon, so each look's start and end can be moved by a few
days.

## Screenshots

These are all on Flarum's default theme.

### Halloween: bunting, a bat, spider webs, a witch hat on each post, and drifting embers

![Halloween](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/halloween.png)

### Fireworks over a night sky on New Year and the Fourth of July

Rockets climb with a trail and burst into peonies, rings, gold willows and
crackles. The sparks fall under gravity and flicker as they die.

![New Year fireworks](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/newyear-fireworks.png)

![Fourth of July](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/july4-fireworks.png)

### Easter: eggs, bunny ears and falling petals

![Easter](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/easter-discussion.png)

### Winter, which runs from the end of Christmas until spring

![Winter](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/winter.png)

### Around the world

![Lunar New Year](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/lunar.png)
![Diwali](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/diwali.png)
![Hanukkah](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/hanukkah.png)
![Ramadan and Eid al-Fitr](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/ramadan.png)

Hats are left off the religious holidays on purpose.

### On a phone

![Christmas on a phone](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/phone-christmas.png)

## The admin page

Every look is listed with the dates it next runs, a switch, **Preview** and
**Edit**. Preview opens the forum wearing that look, for you only, so you can
see Christmas in July before you decide anything.

![The admin page](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/admin.png)

Edit changes a look's dates, colors, garland, ornament, hat, trim and effect,
and its priority. When two looks share a date the higher priority wins:
holidays are 20, seasons 10 and your own 30. **Go back to the original** undoes
your changes to a built-in look.

![Editing Christmas](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/admin-edit.png)

You can also choose the time zone the looks change over in, and switch whole
parts off across the forum: colors, header art, effects, hats, ornaments or
trims.

## For members

Anyone can turn the decorations off under **Settings**. The forum's holiday
colors stay; the garlands, hats, falling things and fireworks go, and they go
the moment the switch is clicked.

![The member setting](https://raw.githubusercontent.com/ernestdefoe/festoon/main/images/member-switch.png)

Festoon also stops all motion for anyone whose device asks for less motion,
and the fireworks pause when the tab is hidden or the banner is scrolled away.

## Light on your forum

- **The right look on the first paint.** Today's look is picked on the server
  in your forum's time zone, so nobody sees the normal colors flash first.
- **No database queries.** Choosing the look reads settings Flarum has already
  loaded. The tests check that a page costs the same number of queries with
  Festoon on or off.
- **Under 1 KB of JavaScript** on every page (gzipped). The fireworks script,
  1.5 KB, is added only on days that have fireworks.
- **Decorations are one cached stylesheet per look**, about 2 KB gzipped,
  written to your assets folder once and then served like Flarum's own CSS.
- **Every animation moves or fades only**, which the browser handles without
  repainting the page.
- **Colors stay readable.** Flarum works out muted text, control backgrounds
  and dark mode from your two colors when it builds the stylesheet. Festoon
  works them out the same way for each look, in light, dark and high-contrast
  modes, and keeps muted text at a contrast of at least 4.5:1.

## Works with

- **Logo Manager**: Festoon uses its seasonal time zone if you have not set
  one, so the logo and the look change at the same midnight. It is not
  required.
- **Bespoke** and **FoF Widgets**: their widgets get the holiday trim and
  ornament.

## Installation

```bash
composer require ernestdefoe/festoon
```

Then enable **Festoon** in the admin panel. It needs PHP's intl extension,
which almost every host has.

## Updating

```bash
composer update ernestdefoe/festoon
php flarum cache:clear
```

## Support

Questions and ideas are welcome on the
[support forum](https://ernestdefoe.online) and in
[GitHub issues](https://github.com/ernestdefoe/festoon/issues).

☕ If you like the things I have built, consider [buying me a coffee](https://buymeacoffee.com/ernestdefoe).

MIT licensed.
