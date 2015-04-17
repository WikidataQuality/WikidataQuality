# -*- coding: utf-8 -*-

from sqlScriptBuilder import sqlScriptBuilder

class TestSqlScriptBuilder():

    def setup_method(self, method):
        #  setup_method is invoked for every test method of a class
        self.builder = sqlScriptBuilder()


    def test_to_comma_seperated_string_standard(self):
        test_string = "{{Q|6581072}}, {{Q|43445}}, {{Q|1052281}}"
        expected_result = "Q6581072,Q43445,Q1052281"
        result = self.builder.to_comma_seperated_string(test_string)
        assert result == expected_result

    def test_to_comma_seperated_string_empty(self):
        test_string = ""
        expected_result = ""
        result = self.builder.to_comma_seperated_string(test_string)
        assert result == expected_result       

    def test_to_comma_seperated_string_unicode(self):
        test_string = "¡“¶¢[]|{}≠¿'≤¥ç √~∫µ…–∞≈å  æœ∂‚ƒ@© πª•ºπ«∑€ ®†Ω¨⁄øπ•   ±‘æœ@∆‚å≈~–"
        expected_result = "¡“¶¢≠¿'≤¥ç√~∫µ…–∞≈åæœ∂‚ƒ@©πª•ºπ«∑€®†Ω¨⁄øπ•±‘æœ@∆‚å≈~–"
        result = self.builder.to_comma_seperated_string(test_string)
        assert result == expected_result

    def test_add_property_standard(self):
        test_value = "P1337"
        expected_result = "P1337"
        self.builder.add_property(test_value)
        assert self.builder.parameters['property'] == expected_result

    def test_add_property_whitepace(self):
        test_value = " P7331 "
        expected_result = "P7331"
        self.builder.add_property(test_value)
        assert self.builder.parameters['property'] == expected_result

    def test_add_property_empty(self):
        test_value = ""
        expected_result = ""
        self.builder.add_property(test_value)
        assert self.builder.parameters['property'] == expected_result

    def test_add_property_multiple(self):
        expected_result = ""
        assert self.builder.parameters['property'] == expected_result

        test_value = "P2992"
        expected_result = "P2992"
        self.builder.add_property(test_value)
        assert self.builder.parameters['property'] == expected_result

        test_value = "P1234567890"
        expected_result = "P1234567890"
        self.builder.add_property(test_value)
        assert self.builder.parameters['property'] == expected_result

    def test_add_classes_standard(self, monkeypatch):
        def mockreturn(path):
            return "Q5,Q95074"
        monkeypatch.setattr(self.builder, 'to_comma_seperated_string', mockreturn)
        test_value = "Q5,Q95074"
        expected_result = "Q5,Q95074"
        self.builder.add_classes(test_value)
        assert self.builder.parameters['class'] == expected_result

    def test_add_classes_empty(self, monkeypatch):
        def mockreturn(path):
            return ""
        monkeypatch.setattr(self.builder, 'to_comma_seperated_string', mockreturn)
        test_value = ""
        expected_result = ""
        self.builder.add_classes(test_value)
        assert self.builder.parameters['class'] == expected_result

    def test_add_classes_multiple(self, monkeypatch):
        def first(path):
            return "first"
        def second(path):
            return "second"
        monkeypatch.setattr(self.builder, 'to_comma_seperated_string', first)
        test_value = "first"
        expected_result = "first"
        self.builder.add_classes(test_value)
        assert self.builder.parameters['class'] == expected_result

        monkeypatch.setattr(self.builder, 'to_comma_seperated_string', second)
        test_value = "second"
        expected_result = "second"
        self.builder.add_classes(test_value)
        assert self.builder.parameters['class'] == expected_result