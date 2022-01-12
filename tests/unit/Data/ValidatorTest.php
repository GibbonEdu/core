<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Data;

use PHPUnit\Framework\TestCase;

/**
 * @covers ValidatorTest
 */
class ValidatorTest extends TestCase
{
    private $validator;

    public function setUp(): void
    {
        $allowableHTML = "br[style],strong[style],b[style],em[style],span[style],p[style],address[style],pre[style|class],h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],table[style],thead[style],tbody[style],tfoot[style],tr[style],td[style|colspan|rowspan],ol[style],ul[style],li[style],blockquote[style],a[style|target|href],img[style|class|src|width|height],video[style],source[style],hr[style],iframe[style|width|height|src|frameborder|allowfullscreen],embed[style],div[class|style],sup[style],sub[style],code[style|class],details[style|class],summary[style|class]";
        
        $this->validator = new Validator($allowableHTML);
    }

    public function testCanRemoveDisallowedTags()
    {
        $input = ['value' => '<script>alert(123)</script>'];
        $expected =  ['value' => 'alert(123)'];

        $this->assertEquals($expected, $this->validator->sanitize($input));
    }

    public function testPassThroughRawValues()
    {
        $input = ['value' => '<script>alert(123)</script>'];
        $expected =  ['value' => '<script>alert(123)</script>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'Raw']));
    }

    public function testCanSanitizePlainText()
    {
        $input = ['value' => '<div class="testing"><b style="font-style: italic;">Hello</b><script>alert(123)</script></div>'];
        $expected =  ['value' => 'Helloalert(123)'];

        $this->assertEquals($expected, $this->validator->sanitize($input));
    }

    public function testCanSanitizeRichText()
    {
        $input = ['value' => '<div>
            <b>Hello</b><script>alert(123)</script>
        </div>'];
        $expected =  ['value' => '<div>
            <b>Hello</b>alert(123)
        </div>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanSanitizeRichTextAttributes()
    {
        $input = ['value' => '<div class="testing" onmouseover="alert(123)">
            <b style="font-style: italic;">Hello</b><script>alert(123)</script>
        </div>'];
        $expected =  ['value' => '<div class="testing">
            <b style="font-style: italic;">Hello</b>alert(123)
        </div>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanSanitizePlainTextUTF8Chinese()
    {
        $input = ['value' => 'Lorem Ipsum，也称乱数假文或者哑元文本， 是印刷及排版领域所常用的虚拟文字。由于曾经一台匿名的打印机刻意打乱了一盒印刷字体从而造出一本字体样品书，Lorem Ipsum从西元15世纪起就被作为此领域的标准文本使用。'];
        $expected =  ['value' => 'Lorem Ipsum，也称乱数假文或者哑元文本， 是印刷及排版领域所常用的虚拟文字。由于曾经一台匿名的打印机刻意打乱了一盒印刷字体从而造出一本字体样品书，Lorem Ipsum从西元15世纪起就被作为此领域的标准文本使用。'];

        $this->assertEquals($expected, $this->validator->sanitize($input));
    }

    public function testCanSanitizePlainTextUTF8Thai()
    {
        $input = ['value' => 'Lorem Ipsum คือ เนื้อหาจำลองแบบเรียบๆ ที่ใช้กันในธุรกิจงานพิมพ์หรืองานเรียงพิมพ์ มันได้กลายมาเป็นเนื้อหาจำลองมาตรฐานของธุรกิจดังกล่าวมาตั้งแต่ศตวรรษที่ 16 เมื่อเครื่องพิมพ์โนเนมเครื่องหนึ่งนำรางตัวพิมพ์มาสลับสับตำแหน่งตัวอักษรเพื่อทำหนังสือตัวอย่าง'];
        $expected =  ['value' => 'Lorem Ipsum คือ เนื้อหาจำลองแบบเรียบๆ ที่ใช้กันในธุรกิจงานพิมพ์หรืองานเรียงพิมพ์ มันได้กลายมาเป็นเนื้อหาจำลองมาตรฐานของธุรกิจดังกล่าวมาตั้งแต่ศตวรรษที่ 16 เมื่อเครื่องพิมพ์โนเนมเครื่องหนึ่งนำรางตัวพิมพ์มาสลับสับตำแหน่งตัวอักษรเพื่อทำหนังสือตัวอย่าง'];

        $this->assertEquals($expected, $this->validator->sanitize($input));
    }

    public function testCanSanitizePlainTextUTF8Arabic()
    {
        $input = ['value' => 'هناك حقيقة مثبتة منذ زمن طويل وهي أن المحتوى المقروء لصفحة ما سيلهي القارئ عن التركيز على الشكل الخارجي للنص أو شكل توضع الفقرات في الصفحة التي يقرأها. ولذلك يتم استخدام طريقة لوريم إيبسوم لأنها تعطي توزيعاَ طبيعياَ -إلى حد ما- للأحرف عوضاً عن استخدام "هنا يوجد محتوى نصي، هنا يوجد محتوى نصي" فتجعلها تبدو (أي الأحرف) وكأنها نص مقروء.'];
        $expected =  ['value' => 'هناك حقيقة مثبتة منذ زمن طويل وهي أن المحتوى المقروء لصفحة ما سيلهي القارئ عن التركيز على الشكل الخارجي للنص أو شكل توضع الفقرات في الصفحة التي يقرأها. ولذلك يتم استخدام طريقة لوريم إيبسوم لأنها تعطي توزيعاَ طبيعياَ -إلى حد ما- للأحرف عوضاً عن استخدام "هنا يوجد محتوى نصي، هنا يوجد محتوى نصي" فتجعلها تبدو (أي الأحرف) وكأنها نص مقروء.'];

        $this->assertEquals($expected, $this->validator->sanitize($input));
    }

    public function testCanSanitizeRichTextUTF8Chinese()
    {
        $input = ['value' => '<div class="testing" onmouseover="alert(123)">Lorem Ipsum，也称乱数假文或者哑元文本， 是印刷及排版领域所常用的虚拟文字。由于曾经一台匿名的打印机刻意打乱了一盒印刷字体从而造出一本字体样品书，Lorem Ipsum从西元15世纪起就被作为此领域的标准文本使用。</div>'];
        $expected =  ['value' => '<div class="testing">Lorem Ipsum，也称乱数假文或者哑元文本， 是印刷及排版领域所常用的虚拟文字。由于曾经一台匿名的打印机刻意打乱了一盒印刷字体从而造出一本字体样品书，Lorem Ipsum从西元15世纪起就被作为此领域的标准文本使用。</div>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanSanitizeRichTextUTF8Thai()
    {
        $input = ['value' => '<div class="testing" onmouseover="alert(123)">Lorem Ipsum คือ เนื้อหาจำลองแบบเรียบๆ ที่ใช้กันในธุรกิจงานพิมพ์หรืองานเรียงพิมพ์ มันได้กลายมาเป็นเนื้อหาจำลองมาตรฐานของธุรกิจดังกล่าวมาตั้งแต่ศตวรรษที่ 16 เมื่อเครื่องพิมพ์โนเนมเครื่องหนึ่งนำรางตัวพิมพ์มาสลับสับตำแหน่งตัวอักษรเพื่อทำหนังสือตัวอย่าง</div>'];
        $expected =  ['value' => '<div class="testing">Lorem Ipsum คือ เนื้อหาจำลองแบบเรียบๆ ที่ใช้กันในธุรกิจงานพิมพ์หรืองานเรียงพิมพ์ มันได้กลายมาเป็นเนื้อหาจำลองมาตรฐานของธุรกิจดังกล่าวมาตั้งแต่ศตวรรษที่ 16 เมื่อเครื่องพิมพ์โนเนมเครื่องหนึ่งนำรางตัวพิมพ์มาสลับสับตำแหน่งตัวอักษรเพื่อทำหนังสือตัวอย่าง</div>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanSanitizeRichTextUTF8Arabic()
    {
        $input = ['value' => '<div class="testing" onmouseover="alert(123)">هناك حقيقة مثبتة منذ زمن طويل وهي أن المحتوى المقروء لصفحة ما سيلهي القارئ عن التركيز على الشكل الخارجي للنص أو شكل توضع الفقرات في الصفحة التي يقرأها. ولذلك يتم استخدام طريقة لوريم إيبسوم لأنها تعطي توزيعاَ طبيعياَ -إلى حد ما- للأحرف عوضاً عن استخدام "هنا يوجد محتوى نصي، هنا يوجد محتوى نصي" فتجعلها تبدو (أي الأحرف) وكأنها نص مقروء.</div>'];
        $expected =  ['value' => '<div class="testing">هناك حقيقة مثبتة منذ زمن طويل وهي أن المحتوى المقروء لصفحة ما سيلهي القارئ عن التركيز على الشكل الخارجي للنص أو شكل توضع الفقرات في الصفحة التي يقرأها. ولذلك يتم استخدام طريقة لوريم إيبسوم لأنها تعطي توزيعاَ طبيعياَ -إلى حد ما- للأحرف عوضاً عن استخدام "هنا يوجد محتوى نصي، هنا يوجد محتوى نصي" فتجعلها تبدو (أي الأحرف) وكأنها نص مقروء.</div>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }
    
}
